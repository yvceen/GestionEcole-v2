<?php

namespace Tests\Feature\Parent;

use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\Homework;
use App\Models\HomeworkAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class ParentAttachmentAndNotificationTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_parent_can_download_course_attachment_for_owned_child_classroom(): void
    {
        Storage::fake('public');

        $school = $this->createSchool(['slug' => 'parent-course-download']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $this->createStudentForSchool($school, $classroom, $parent);

        $course = Course::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => null,
            'created_by_user_id' => null,
            'title' => 'Course attachment',
            'description' => null,
            'published_at' => now(),
            'status' => 'approved',
        ]);

        Storage::disk('public')->put('courses/owned.pdf', 'course attachment');

        $attachment = CourseAttachment::create([
            'school_id' => $school->id,
            'course_id' => $course->id,
            'original_name' => 'owned.pdf',
            'path' => 'courses/owned.pdf',
            'mime' => 'application/pdf',
            'size' => 16,
        ]);

        $response = $this->actingAs($parent)
            ->get(route('parent.courses.attachments.download', $attachment, false));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=owned.pdf');
    }

    public function test_parent_cannot_download_attachment_for_other_classroom(): void
    {
        Storage::fake('public');

        $school = $this->createSchool(['slug' => 'parent-attachment-deny']);
        $ownerParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroomA = $this->createClassroomForSchool($school);
        $classroomB = $this->createClassroomForSchool($school, [], ['section' => 'B', 'name' => 'Class B']);
        $this->createStudentForSchool($school, $classroomA, $ownerParent);
        $this->createStudentForSchool($school, $classroomB, $otherParent);

        $homework = Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroomB->id,
            'teacher_id' => $this->createUserForSchool($school, User::ROLE_TEACHER)->id,
            'title' => 'Foreign homework',
            'status' => 'approved',
        ]);

        Storage::disk('public')->put('homeworks/foreign.pdf', 'foreign attachment');

        $attachment = HomeworkAttachment::create([
            'school_id' => $school->id,
            'homework_id' => $homework->id,
            'original_name' => 'foreign.pdf',
            'path' => 'homeworks/foreign.pdf',
            'mime' => 'application/pdf',
            'size' => 18,
        ]);

        $this->actingAs($ownerParent)
            ->get(route('parent.homeworks.attachments.download', $attachment, false))
            ->assertForbidden();
    }

    public function test_parent_notification_routes_are_owner_scoped_and_mark_items_read(): void
    {
        $school = $this->createSchool(['slug' => 'parent-notifications']);
        $parentA = $this->createUserForSchool($school, User::ROLE_PARENT);
        $parentB = $this->createUserForSchool($school, User::ROLE_PARENT);
        $notificationA = $this->createNotificationForUser($parentA, [
            'type' => 'appointment',
            'data' => ['url' => route('parent.appointments.create', absolute: false)],
        ]);
        $notificationB = $this->createNotificationForUser($parentB);

        $openResponse = $this->actingAs($parentA)
            ->get(route('parent.notifications.open', $notificationA, false));

        $openResponse->assertRedirect(route('parent.appointments.create', absolute: false));
        $this->assertNotNull($notificationA->fresh()->read_at);

        $this->actingAs($parentA)
            ->post(route('parent.notifications.read', $notificationB, false))
            ->assertForbidden();
    }
}
