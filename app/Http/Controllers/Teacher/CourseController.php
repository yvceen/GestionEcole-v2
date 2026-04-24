<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $teacher   = auth()->user();
        $teacherId = $teacher?->id;

        $q = Course::query()
            // ⚠️ إذا Course عندو GlobalScope ديال المدرسة (BelongsToSchool) راه هاد where ماشي ضروري
            // ولكن خليته باش يكون واضح و robust
            ->where('courses.school_id', $schoolId)
            ->with(['classroom.level', 'attachments']);

        // ✅ Strategy 1: إذا teacher_id كاين ف courses => فلتر بالدروس ديال هاد الأستاذ
        if (Schema::hasColumn('courses', 'teacher_id')) {
            $q->where('courses.teacher_id', $teacherId);
        } else {
            // ✅ Strategy 2: إذا teacher_id ماكاينش => فلتر بالclassrooms المعيّنين للأستاذ
            // (باش ما يشوفش دروس ديال ناس خرين)
            if ($teacher && method_exists($teacher, 'teacherClassrooms')) {
                $classroomIds = $teacher->teacherClassrooms()
                    ->where('classrooms.school_id', $schoolId)
                    ->pluck('classrooms.id')
                    ->all();

                if (!empty($classroomIds) && Schema::hasColumn('courses', 'classroom_id')) {
                    $q->whereIn('courses.classroom_id', $classroomIds);
                } else {
                    // إذا ماعندوش أقسام => خليه يشوف والو
                    $q->whereRaw('1=0');
                }
            } else {
                // إذا relation ماكايناش => خليه يشوف والو باش ما يكونش data leak
                $q->whereRaw('1=0');
            }
        }

        $courses = $q->latest('courses.created_at')->paginate(12);

        return view('teacher.courses.index', compact('courses'));
    }

    public function create(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $teacher = auth()->user();

        // ✅ الأستاذ يشوف غير الأقسام لي متعيّنين ليه
        // وإذا teacherClassrooms غير موجودة => رجّع collection خاوية
        if (!$teacher || !method_exists($teacher, 'teacherClassrooms')) {
            $classrooms = collect();
        } else {
            $classrooms = $teacher->teacherClassrooms()
                ->where('classrooms.school_id', $schoolId)
                ->select('classrooms.id', 'classrooms.name', 'classrooms.level_id')
                ->with('level:id,name,school_id')
                ->orderBy('classrooms.name')
                ->get();
        }

        return view('teacher.courses.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $teacher   = auth()->user();
        $teacherId = $teacher?->id;

        if (!$teacher) abort(403);

        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],

            // ✅ ملفات: حدّدنا types باش ما يدخلش أي حاجة (أمنياً أفضل)
            'files'        => ['nullable', 'array'],
            'files.*'      => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,ppt,pptx,xls,xlsx'],
        ]);

        // ✅ 1) تأكد القسم كاين و تابع لنفس المدرسة
        $classroomExists = DB::table('classrooms')
            ->where('id', $data['classroom_id'])
            ->where('school_id', $schoolId)
            ->exists();

        if (!$classroomExists) {
            abort(403, "Classe invalide (hors de votre école).");
        }

        // ✅ 2) تأكد الأستاذ فعلاً معيين لهاد القسم (باش ما يحقنش classroom_id)
        $allowed = false;
        if (method_exists($teacher, 'teacherClassrooms')) {
            $allowed = $teacher->teacherClassrooms()
                ->where('classrooms.school_id', $schoolId)
                ->where('classrooms.id', $data['classroom_id'])
                ->exists();
        }

        if (!$allowed) {
            abort(403, "Vous n'êtes pas affecté à cette classe.");
        }

        // ✅ payload مرن حسب columns اللي كاينين ف DB
        $payload = [
            'school_id'    => $schoolId,
            'classroom_id' => $data['classroom_id'],
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
        ];

        // teacher_id إلا كان كاين
        if (Schema::hasColumn('courses', 'teacher_id')) {
            $payload['teacher_id'] = $teacherId;
        }

        // file column (legacy) إذا كان موجود نخليه null
        if (Schema::hasColumn('courses', 'file')) {
            $payload['file'] = null;
        }

        $course = Course::create($payload);

        // ============================
        // Attachments (Safe)
        // ============================

        // نحددو table ديال attachments: غالباً course_attachments
        $attachmentsTable = null;
        if (Schema::hasTable('course_attachments')) {
            $attachmentsTable = 'course_attachments';
        } elseif (Schema::hasTable('attachments')) {
            $attachmentsTable = 'attachments';
        } else {
            // نخليها null => ما ندير والو باش ما يطيحش
            $attachmentsTable = null;
        }

        if ($attachmentsTable && $request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (!$file) continue;

                $path = $file->store("schools/{$schoolId}/courses/{$course->id}", 'public');

                $attachPayload = [
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime'          => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                ];

                // إذا كان عندك school_id ف attachments table
                if (Schema::hasColumn($attachmentsTable, 'school_id')) {
                    $attachPayload['school_id'] = $schoolId;
                }

                // إذا كان عندك course_id ف attachments table (غالباً كاين)
                if (Schema::hasColumn($attachmentsTable, 'course_id')) {
                    $attachPayload['course_id'] = $course->id;
                }

                // إذا العلاقة موجودة ف model (attachments()) استعملها
                if (method_exists($course, 'attachments')) {
                    $course->attachments()->create($attachPayload);
                } else {
                    // fallback insert مباشر
                    DB::table($attachmentsTable)->insert(array_merge($attachPayload, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }

        return redirect()
            ->route('teacher.courses.index')
            ->with('success', 'Cours ajouté ✅');
    }
}