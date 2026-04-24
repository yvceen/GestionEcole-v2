<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\AppNotification;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait InteractsWithStudentPortal
{
    private function schoolIdOrFail(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    private function currentStudent(array $with = []): Student
    {
        return Student::query()
            ->with($with)
            ->where('school_id', $this->schoolIdOrFail())
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    private function visibleCoursesQuery(Student $student): Builder
    {
        return Course::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->where('classroom_id', $student->classroom_id)
            ->when(
                Schema::hasTable('courses') && Schema::hasColumn('courses', 'status'),
                fn (Builder $query) => $query->whereIn('status', ['approved', 'confirmed'])
            );
    }

    private function visibleHomeworksQuery(Student $student): Builder
    {
        return Homework::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->where('classroom_id', $student->classroom_id)
            ->when(
                Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'status'),
                fn (Builder $query) => $query->whereIn('status', ['approved', 'confirmed'])
            );
    }

    private function unreadNotificationsCount(): int
    {
        $userId = (int) auth()->id();
        if ($userId <= 0 || !Schema::hasTable('notifications')) {
            return 0;
        }

        $userColumn = Schema::hasColumn('notifications', 'recipient_user_id')
            ? 'recipient_user_id'
            : 'user_id';

        return AppNotification::query()
            ->where($userColumn, $userId)
            ->whereNull('read_at')
            ->count();
    }
}
