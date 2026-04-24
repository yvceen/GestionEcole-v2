<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Classroom;
use App\Models\User;
use App\Services\ActivityParticipationService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityParticipationService $participants,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $classroomId = $request->integer('classroom_id');
        $teacherId = $request->integer('teacher_id');
        $status = trim((string) $request->get('status', ''));

        $activities = Activity::query()
            ->where('school_id', $schoolId)
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($teacherId > 0, fn ($query) => $query->where('teacher_id', $teacherId))
            ->with(['classroom:id,name', 'teacher:id,name'])
            ->withCount([
                'participants',
                'participants as confirmed_count' => fn ($query) => $query->where('confirmation_status', ActivityParticipant::CONFIRMATION_CONFIRMED),
                'participants as attended_count' => fn ($query) => $query->where('attendance_status', ActivityParticipant::ATTENDANCE_PRESENT),
            ])
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'confirmed') {
                    $query->whereHas('participants', fn ($participants) => $participants->where('confirmation_status', ActivityParticipant::CONFIRMATION_CONFIRMED));
                }
                if ($status === 'pending') {
                    $query->whereHas('participants', fn ($participants) => $participants->where('confirmation_status', ActivityParticipant::CONFIRMATION_PENDING));
                }
            })
            ->orderBy('start_date')
            ->paginate(15)
            ->withQueryString();

        $classrooms = Classroom::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);
        $teachers = User::query()->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER)->orderBy('name')->get(['id', 'name']);

        return view('school-life.activities.index', compact('activities', 'classrooms', 'teachers', 'classroomId', 'teacherId', 'status'));
    }

    public function show(Activity $activity)
    {
        $activity = $this->resolveActivity($activity);
        $this->participants->ensureParticipants($activity);
        $activity->load([
            'classroom:id,name',
            'teacher:id,name',
            'participants.student:id,full_name,classroom_id,parent_user_id',
            'participants.student.classroom:id,name',
            'participants.student.parentUser:id,name,phone',
            'reports.author:id,name',
        ]);

        return view('school-life.activities.show', compact('activity'));
    }

    public function updateParticipant(Request $request, Activity $activity, ActivityParticipant $participant)
    {
        $activity = $this->resolveActivity($activity);
        abort_unless((int) $participant->activity_id === (int) $activity->id && (int) $participant->school_id === $this->schoolId(), 404);

        $data = $request->validate([
            'confirmation_status' => ['nullable', 'in:' . implode(',', [
                ActivityParticipant::CONFIRMATION_PENDING,
                ActivityParticipant::CONFIRMATION_CONFIRMED,
                ActivityParticipant::CONFIRMATION_DECLINED,
            ])],
            'attendance_status' => ['nullable', 'in:' . implode(',', [
                ActivityParticipant::ATTENDANCE_PRESENT,
                ActivityParticipant::ATTENDANCE_ABSENT,
            ])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (!empty($data['confirmation_status']) && $data['confirmation_status'] !== ActivityParticipant::CONFIRMATION_PENDING) {
            $data['confirmed_at'] = now();
        }
        if (!empty($data['attendance_status'])) {
            $data['attended_at'] = now();
        }

        $participant->update([
            'confirmation_status' => $data['confirmation_status'] ?? $participant->confirmation_status,
            'attendance_status' => $data['attendance_status'] ?? $participant->attendance_status,
            'confirmed_at' => $data['confirmed_at'] ?? $participant->confirmed_at,
            'attended_at' => $data['attended_at'] ?? $participant->attended_at,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Participation mise a jour.');
    }

    public function storeReport(Request $request, Activity $activity)
    {
        $activity = $this->resolveActivity($activity);

        $data = $request->validate([
            'report_text' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $activity->reports()->create([
            'school_id' => $this->schoolId(),
            'created_by_user_id' => auth()->id(),
            'report_text' => $data['report_text'],
            'image_path' => $request->file('image')?->store('activity-reports', 'public'),
        ]);

        return back()->with('success', 'Compte rendu ajoute.');
    }

    private function resolveActivity(Activity $activity): Activity
    {
        abort_unless((int) $activity->school_id === $this->schoolId(), 404);

        return $activity;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
