<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Student;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use InteractsWithParentPortal;

    public function index()
    {
        $children = $this->ownedChildren(['classroom:id,name']);
        $childIds = $children->pluck('id');

        $activities = Activity::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereHas('participants', fn ($query) => $query->whereIn('student_id', $childIds))
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'participants' => fn ($query) => $query->whereIn('student_id', $childIds)->with('student:id,full_name,classroom_id'),
                'reports.author:id,name',
            ])
            ->orderBy('start_date')
            ->paginate(12);

        return view('parent.activities.index', compact('activities', 'children'));
    }

    public function confirm(Request $request, Activity $activity)
    {
        abort_unless((int) $activity->school_id === $this->schoolIdOrFail(), 404);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'confirmation_status' => ['required', 'in:' . implode(',', [
                ActivityParticipant::CONFIRMATION_CONFIRMED,
                ActivityParticipant::CONFIRMATION_DECLINED,
            ])],
        ]);

        $student = $this->resolveOwnedStudent(Student::findOrFail((int) $data['student_id']));
        $participant = ActivityParticipant::query()->firstOrCreate([
            'school_id' => $this->schoolIdOrFail(),
            'activity_id' => $activity->id,
            'student_id' => $student->id,
        ], [
            'confirmation_status' => ActivityParticipant::CONFIRMATION_PENDING,
        ]);

        $participant->update([
            'confirmation_status' => $data['confirmation_status'],
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Participation mise a jour.');
    }
}
