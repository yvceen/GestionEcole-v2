<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\CourseAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CoursesController extends Controller
{
    use InteractsWithStudentPortal;

    public function index(Request $request)
    {
        $student = $this->currentStudent();

        $q = trim((string) $request->get('q', ''));

        $courses = $this->visibleCoursesQuery($student)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->with(['classroom.level', 'teacher', 'attachments'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('student.courses.index', compact('student', 'courses', 'q'));
    }

    public function download(CourseAttachment $attachment)
    {
        $student = $this->currentStudent();
        $schoolId = $this->schoolIdOrFail();

        abort_unless((int) $attachment->school_id === $schoolId, 403);

        $attachment->load('course');

        abort_unless($attachment->course, 404);
        abort_unless((int) $attachment->course->school_id === $schoolId, 404);
        abort_unless((int) $attachment->course->classroom_id === (int) $student->classroom_id, 403);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->path), 404, 'File not found.');

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
