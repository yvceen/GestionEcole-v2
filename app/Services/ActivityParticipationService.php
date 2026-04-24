<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Student;

class ActivityParticipationService
{
    public function ensureParticipants(Activity $activity): int
    {
        $studentIds = Student::query()
            ->where('school_id', (int) $activity->school_id)
            ->active()
            ->when($activity->classroom_id, fn ($query) => $query->where('classroom_id', (int) $activity->classroom_id))
            ->pluck('id');

        $created = 0;

        foreach ($studentIds as $studentId) {
            $participant = ActivityParticipant::query()->firstOrCreate([
                'activity_id' => $activity->id,
                'student_id' => (int) $studentId,
            ], [
                'school_id' => (int) $activity->school_id,
                'confirmation_status' => ActivityParticipant::CONFIRMATION_PENDING,
            ]);

            if ($participant->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
