<?php

namespace Tests\Feature\Admin;

use App\Models\AppNotification;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class MessagingApprovalWorkflowTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_teacher_message_to_parent_is_created_as_pending(): void
    {
        $school = $this->createSchool(['slug' => 'messages-a']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $response = $this->actingAs($teacher)->post(route('teacher.messages.store', absolute: false), [
            'subject' => 'Progress update',
            'body' => 'Student progress looks stable this week.',
            'parent_ids' => [$parent->id],
        ]);

        $response->assertRedirect(route('teacher.messages.index', absolute: false));
        $this->assertDatabaseHas('messages', [
            'school_id' => $school->id,
            'sender_id' => $teacher->id,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_pending_teacher_message_and_notify_recipient(): void
    {
        $school = $this->createSchool(['slug' => 'messages-approve']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $message = Message::create([
            'school_id' => $school->id,
            'sender_id' => $teacher->id,
            'sender_role' => $teacher->role,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'subject' => 'Approval needed',
            'body' => 'Pending teacher message.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.messages.pending', absolute: false))
            ->post(route('admin.messages.approve', $message, false));

        $response->assertRedirect(route('admin.messages.pending', absolute: false));
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'message',
        ]);
    }

    public function test_foreign_school_admin_cannot_approve_pending_message(): void
    {
        $schoolA = $this->createSchool(['slug' => 'messages-origin']);
        $schoolB = $this->createSchool(['slug' => 'messages-foreign']);
        $teacher = $this->createUserForSchool($schoolA, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($schoolA, User::ROLE_PARENT);
        $foreignAdmin = $this->createUserForSchool($schoolB, User::ROLE_ADMIN);

        $message = Message::create([
            'school_id' => $schoolA->id,
            'sender_id' => $teacher->id,
            'sender_role' => $teacher->role,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'subject' => 'Cross-school blocked',
            'body' => 'Pending teacher message.',
            'status' => 'pending',
        ]);

        $this->actingAs($foreignAdmin)
            ->post(route('admin.messages.approve', $message, false))
            ->assertNotFound();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'message',
        ]);
    }

    public function test_admin_can_reject_pending_message_with_reason(): void
    {
        $school = $this->createSchool(['slug' => 'messages-reject']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $message = Message::create([
            'school_id' => $school->id,
            'sender_id' => $teacher->id,
            'sender_role' => $teacher->role,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'subject' => 'Reject me',
            'body' => 'Pending teacher message.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.messages.pending', absolute: false))
            ->post(route('admin.messages.reject', $message, false), [
                'reason' => 'Missing context for parents.',
            ]);

        $response->assertRedirect(route('admin.messages.pending', absolute: false));
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'rejected',
            'rejection_reason' => 'Missing context for parents.',
        ]);
    }

    public function test_parent_reply_is_added_to_existing_thread_and_sent_directly(): void
    {
        $school = $this->createSchool(['slug' => 'messages-parent-reply']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $message = Message::create([
            'school_id' => $school->id,
            'sender_id' => $teacher->id,
            'sender_role' => $teacher->role,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'subject' => 'Initial thread',
            'body' => 'Teacher message.',
            'status' => 'approved',
            'thread_id' => null,
        ]);

        $message->update(['thread_id' => $message->id]);

        $response = $this->actingAs($parent)->post(route('parent.messages.store', absolute: false), [
            'reply_to_id' => $message->id,
            'subject' => 'Re: Initial thread',
            'body' => 'Thank you for the update.',
        ]);

        $reply = Message::query()->where('sender_id', $parent->id)->latest('id')->first();

        $response->assertRedirect(route('parent.messages.show', $reply, false));
        $this->assertNotNull($reply);
        $this->assertDatabaseHas('messages', [
            'id' => $reply->id,
            'reply_to_id' => $message->id,
            'thread_id' => $message->id,
            'recipient_type' => 'user',
            'recipient_id' => $teacher->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $teacher->id,
            'type' => 'message',
        ]);
    }

    public function test_teacher_reply_to_parent_message_stays_pending_until_admin_review(): void
    {
        $school = $this->createSchool(['slug' => 'messages-teacher-reply']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $message = Message::create([
            'school_id' => $school->id,
            'sender_id' => $parent->id,
            'sender_role' => $parent->role,
            'recipient_type' => 'user',
            'recipient_id' => $teacher->id,
            'subject' => 'Question',
            'body' => 'Parent message.',
            'status' => 'approved',
            'thread_id' => null,
        ]);

        $message->update(['thread_id' => $message->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.messages.store', absolute: false), [
            'reply_to_id' => $message->id,
            'subject' => 'Re: Question',
            'body' => 'Teacher reply needing approval.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'sender_id' => $teacher->id,
            'reply_to_id' => $message->id,
            'thread_id' => $message->id,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'status' => 'pending',
        ]);
    }

    public function test_opening_a_parent_thread_marks_its_message_notifications_as_read(): void
    {
        $school = $this->createSchool(['slug' => 'messages-parent-read']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $message = Message::create([
            'school_id' => $school->id,
            'sender_id' => $teacher->id,
            'sender_role' => $teacher->role,
            'recipient_type' => 'user',
            'recipient_id' => $parent->id,
            'subject' => 'Unread thread',
            'body' => 'Please review this message.',
            'status' => 'approved',
            'thread_id' => null,
        ]);

        $message->update(['thread_id' => $message->id]);

        $notification = AppNotification::create([
            'recipient_user_id' => $parent->id,
            'user_id' => $parent->id,
            'recipient_role' => $parent->role,
            'type' => 'message',
            'title' => 'Unread thread',
            'body' => 'Please review this message.',
            'data' => ['message_id' => $message->id],
            'read_at' => null,
        ]);

        $indexResponse = $this->actingAs($parent)->get(route('parent.messages.index', absolute: false));
        $threads = $indexResponse->viewData('messages')->getCollection();

        $this->assertSame(1, (int) data_get($threads->first(), 'unread_count'));

        $this->actingAs($parent)
            ->get(route('parent.messages.show', $message, false))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        $updatedThreads = $this->actingAs($parent)
            ->get(route('parent.messages.index', absolute: false))
            ->viewData('messages')
            ->getCollection();

        $this->assertSame(0, (int) data_get($updatedThreads->first(), 'unread_count'));
    }
}
