<?php

use App\Http\Controllers\AttendanceScanController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\MobileAttendanceScanController;
use App\Http\Controllers\Api\MobileAgendaController;
use App\Http\Controllers\Api\MobileAppointmentController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\MobileDocumentController;
use App\Http\Controllers\Api\MobileBehaviorFeedController;
use App\Http\Controllers\Api\MobileHomeworkController;
use App\Http\Controllers\Api\MobileMessageController;
use App\Http\Controllers\Api\MobileNewsController;
use App\Http\Controllers\Api\MobileNotificationController;
use App\Http\Controllers\Api\MobilePasswordController;
use App\Http\Controllers\Api\MobileSchoolLifeController;
use App\Http\Controllers\Api\MobileTeacherAttendanceController;
use App\Http\Controllers\Api\MobileTeacherHomeworkController;
use App\Http\Controllers\Api\MobileTransportController;
use App\Http\Middleware\AuthenticateMobileApiToken;
use App\Http\Middleware\EnsureApiSchoolAccess;
use App\Http\Middleware\IdentifySchoolFromSubdomain;
use App\Http\Middleware\SetCurrentSchool;
use Illuminate\Support\Facades\Route;

Route::middleware([IdentifySchoolFromSubdomain::class])->group(function (): void {
    Route::post('/login', [MobileAuthController::class, 'login'])->name('api.login');

    Route::middleware([AuthenticateMobileApiToken::class, SetCurrentSchool::class, EnsureApiSchoolAccess::class])->group(function (): void {
        Route::post('/logout', [MobileAuthController::class, 'logout'])->name('api.logout');
        Route::post('/password', [MobilePasswordController::class, 'update'])->name('api.password.update');
        Route::get('/me', [MobileAuthController::class, 'me'])->name('api.me');
        Route::get('/dashboard', [MobileDashboardController::class, 'show'])->name('api.dashboard.show');
        Route::get('/agenda', [MobileAgendaController::class, 'index'])->name('api.agenda.index');
        Route::get('/news', [MobileNewsController::class, 'index'])->name('api.news.index');
        Route::get('/news/{news}', [MobileNewsController::class, 'show'])->whereNumber('news')->name('api.news.show');
        Route::get('/transport', [MobileTransportController::class, 'show'])->name('api.transport.show');
        Route::get('/documents', [MobileDocumentController::class, 'index'])->name('api.documents.index');
        Route::get('/documents/{document}/download', DocumentDownloadController::class)
            ->whereNumber('document')
            ->name('api.documents.download');
        Route::get('/behavior-notes', [MobileBehaviorFeedController::class, 'index'])->name('api.behavior-notes.index');
        Route::get('/appointments', [MobileAppointmentController::class, 'index'])->name('api.appointments.index');
        Route::post('/appointments', [MobileAppointmentController::class, 'store'])->name('api.appointments.store');
        Route::post('/mobile/attendance/scan', [MobileAttendanceScanController::class, 'store'])->name('api.mobile.attendance.scan');
        Route::post('/mobile/device-tokens', [DeviceTokenController::class, 'store'])->name('api.mobile.device-tokens.store');
        Route::get('/notifications', [MobileNotificationController::class, 'index'])->name('api.notifications.index');
        Route::post('/notifications/{notification}/read', [MobileNotificationController::class, 'markRead'])
            ->name('api.notifications.read');
        Route::get('/mobile/messages', [MobileMessageController::class, 'index'])->name('api.mobile.messages.index');
        Route::get('/mobile/messages/compose', [MobileMessageController::class, 'compose'])
            ->name('api.mobile.messages.compose');
        Route::post('/mobile/messages', [MobileMessageController::class, 'store'])
            ->name('api.mobile.messages.store');
        Route::get('/mobile/messages/{thread}', [MobileMessageController::class, 'show'])
            ->whereNumber('thread')
            ->name('api.mobile.messages.show');
        Route::post('/mobile/messages/{thread}/reply', [MobileMessageController::class, 'reply'])
            ->whereNumber('thread')
            ->name('api.mobile.messages.reply');
        Route::post('/mobile/messages/{thread}/read', [MobileMessageController::class, 'markRead'])
            ->whereNumber('thread')
            ->name('api.mobile.messages.read');
        Route::get('/mobile/homeworks', [MobileHomeworkController::class, 'index'])->name('api.mobile.homeworks.index');
        Route::get('/mobile/homeworks/attachments/{attachment}', [MobileHomeworkController::class, 'downloadAttachment'])
            ->whereNumber('attachment')
            ->name('api.mobile.homeworks.attachments.download');
        Route::get('/mobile/homeworks/{homework}', [MobileHomeworkController::class, 'show'])
            ->whereNumber('homework')
            ->name('api.mobile.homeworks.show');
        Route::post('/mobile/homeworks/{homework}/read', [MobileHomeworkController::class, 'markRead'])
            ->whereNumber('homework')
            ->name('api.mobile.homeworks.read');
        Route::get('/mobile/teacher/attendance/meta', [MobileTeacherAttendanceController::class, 'meta'])
            ->name('api.mobile.teacher.attendance.meta');
        Route::get('/mobile/teacher/attendance/register', [MobileTeacherAttendanceController::class, 'show'])
            ->name('api.mobile.teacher.attendance.show');
        Route::post('/mobile/teacher/attendance/register', [MobileTeacherAttendanceController::class, 'store'])
            ->name('api.mobile.teacher.attendance.store');
        Route::get('/mobile/teacher/homeworks/meta', [MobileTeacherHomeworkController::class, 'meta'])
            ->name('api.mobile.teacher.homeworks.meta');
        Route::post('/mobile/teacher/homeworks', [MobileTeacherHomeworkController::class, 'store'])
            ->name('api.mobile.teacher.homeworks.store');
        Route::get('/school-life/overview', [MobileSchoolLifeController::class, 'show'])->name('api.school-life.overview');
        Route::post('/school-life/pickup-requests/{pickupRequest}/transition', [MobileSchoolLifeController::class, 'transitionPickup'])
            ->name('api.school-life.pickup-requests.transition');
    });
});

Route::middleware(['web', 'auth', 'school.active'])->post('/attendance/scan', [AttendanceScanController::class, 'store'])
    ->name('api.attendance.scan');

Route::middleware(['web', 'auth', 'school.active'])->post('/device-tokens', [DeviceTokenController::class, 'store'])
    ->name('web.device-tokens.store');
