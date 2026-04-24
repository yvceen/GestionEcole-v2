<?php

use App\Services\AttendanceAutoAbsentService;
use App\Services\PaymentReminderService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:mark-auto-absent {--school=} {--force}', function () {
    $schoolId = (int) ($this->option('school') ?: 0);
    $count = app(AttendanceAutoAbsentService::class)->markDueAbsences(
        $schoolId > 0 ? $schoolId : null,
        now(),
        (bool) $this->option('force')
    );

    $this->info("Auto absences marked: {$count}");
})->purpose('Mark unscanned students as absent after school cutoff time');

Artisan::command('push:test-user {user_id} {--title=} {--body=}', function () {
    $userId = (int) $this->argument('user_id');
    $title = (string) ($this->option('title') ?: 'Test push MyEdu');
    $body = (string) ($this->option('body') ?: 'Notification test envoyee.');

    $sent = app(PushNotificationService::class)->sendToUsers(
        [$userId],
        $title,
        $body,
        ['type' => 'push_test_cli']
    );

    $this->info("Push sent to {$sent} Android token(s).");
})->purpose('Send a test push notification to one user');

Artisan::command('finance:send-payment-reminders', function () {
    $sent = app(PaymentReminderService::class)->sendDueScheduledReminders(now());
    $this->info("Finance reminders sent: {$sent}");
})->purpose('Send school-scoped payment reminders due today');

Schedule::command('attendance:mark-auto-absent')
    ->weekdays()
    ->everyTenMinutes()
    ->between('07:00', '12:00');

Schedule::command('finance:send-payment-reminders')
    ->dailyAt('08:00');
