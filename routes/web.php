<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceScanController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransportOpsController;

// ======================
// Admin
// ======================
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentLibraryController;
use App\Http\Controllers\Admin\StructureController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentFeePlanController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ParentFeesController;
use App\Http\Controllers\Admin\TeacherPedagogyController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\TransportController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\TransportAssignmentController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\PushTestController as AdminPushTestController;
use App\Http\Controllers\Admin\SchoolLifeController as AdminSchoolLifeController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\HomeworkController as AdminHomeworkController;
use App\Http\Controllers\Admin\FinanceReminderController as AdminFinanceReminderController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AcademicYearController as AdminAcademicYearController;
use App\Http\Controllers\Admin\StudentPromotionController as AdminStudentPromotionController;
use App\Http\Controllers\Admin\TimetableController as AdminTimetableController;
use App\Http\Controllers\Admin\TimetableSettingsController as AdminTimetableSettingsController;

// ======================
// Super Admin
// ======================
use App\Http\Controllers\SuperAdmin\DashboardController as SuperDashboardController;
use App\Http\Controllers\SuperAdmin\SchoolController as SuperSchoolController;

// ======================
// Parent
// ======================
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\CoursesController as ParentCoursesController;
use App\Http\Controllers\Parent\HomeworkController as ParentHomeworksController;
use App\Http\Controllers\Parent\MessageController as ParentMessageController;
use App\Http\Controllers\Parent\AppointmentController as ParentAppointmentController;
use App\Http\Controllers\Parent\ChildrenController as ParentChildrenController;
use App\Http\Controllers\Parent\ActivityController as ParentActivityController;
use App\Http\Controllers\Parent\NotificationController as ParentNotificationController;
use App\Http\Controllers\Parent\TimetableController as ParentTimetableController;
use App\Http\Controllers\Parent\GradesController as ParentGradesController;
use App\Http\Controllers\Parent\AttendanceController as ParentAttendanceController;
use App\Http\Controllers\Parent\FinanceController as ParentFinanceController;
use App\Http\Controllers\Parent\TransportController as ParentTransportController;


// ======================
// Teacher
// ======================
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\CourseController as TeacherCourseController;
use App\Http\Controllers\Teacher\HomeworkController as TeacherHomeworkController;
use App\Http\Controllers\Teacher\AssessmentsController as TeacherAssessmentsController;
use App\Http\Controllers\Teacher\GradesController as TeacherGradesController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\MessageController as TeacherMessageController;
use App\Http\Controllers\Teacher\TimetableController as TeacherTimetableController;

// ======================
// Student
// ======================
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\CoursesController as StudentCoursesController;
use App\Http\Controllers\Student\ActivityController as StudentActivityController;
use App\Http\Controllers\Student\HomeworksController as StudentHomeworksController;
use App\Http\Controllers\Student\TimetableController as StudentTimetableController;
use App\Http\Controllers\Student\GradesController as StudentGradesController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\FinanceController as StudentFinanceController;
use App\Http\Controllers\Student\TransportController as StudentTransportController;

// ======================
// Director
// ======================
use App\Http\Controllers\Director\DashboardController as DirectorDashboardController;
use App\Http\Controllers\Director\MonitoringController as DirectorMonitoringController;
use App\Http\Controllers\Director\TeachersController as DirectorTeachersController;
use App\Http\Controllers\Director\StudentsController as DirectorStudentsController;
use App\Http\Controllers\Director\ParentsController as DirectorParentsController;
use App\Http\Controllers\Director\ExportsController as DirectorExportsController;
use App\Http\Controllers\Director\ResultsController as DirectorResultsController;
use App\Http\Controllers\Director\StudentFicheController as DirectorStudentFicheController;
use App\Http\Controllers\Director\SupportController as DirectorSupportController;
use App\Http\Controllers\Director\CouncilController as DirectorCouncilController;
use App\Http\Controllers\Director\ReportsController as DirectorReportsController;
use App\Http\Controllers\Director\MessageController as DirectorMessageController;
use App\Http\Controllers\Director\AttendanceController as DirectorAttendanceController;
use App\Http\Controllers\Documents\RegistrationRequirementController;
use App\Http\Controllers\Parent\PickupRequestController as ParentPickupRequestController;
use App\Http\Controllers\SchoolLife\AttendanceController as SchoolLifeAttendanceController;
use App\Http\Controllers\SchoolLife\AttendanceScanController as SchoolLifeAttendanceScanController;
use App\Http\Controllers\SchoolLife\ActivityController as SchoolLifeActivityController;
use App\Http\Controllers\SchoolLife\BehaviorController as SchoolLifeBehaviorController;
use App\Http\Controllers\SchoolLife\DashboardController as SchoolLifeDashboardController;
use App\Http\Controllers\SchoolLife\GradesController as SchoolLifeGradesController;
use App\Http\Controllers\SchoolLife\HomeworkController as SchoolLifeHomeworkController;
use App\Http\Controllers\SchoolLife\PickupRequestController as SchoolLifePickupRequestController;
use App\Http\Controllers\SchoolLife\StudentsController as SchoolLifeStudentsController;

use App\Http\Middleware\DirectorOnly;

// ======================
// Public
// ======================
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : view('welcome');
});

Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');
Route::get('/school-inactive', function () {
    return view('errors.school-inactive');
})->name('school.inactive');

// ======================
// After login redirect
// ======================
Route::get('/dashboard', function () {
    $user = Auth::user();
    if (!$user) return redirect('/');

    return match ($user->role) {
        'super_admin' => redirect()->route('super.dashboard'),
        'admin'       => redirect()->route('admin.dashboard'),
        'director'    => redirect()->route('director.dashboard'),
        'teacher'     => redirect()->route('teacher.dashboard'),
        'parent'      => redirect()->route('parent.dashboard'),
        'student'     => redirect()->route('student.dashboard'),
        'school_life' => redirect()->route('school-life.dashboard'),
        default       => redirect('/'),
    };
})->middleware('auth')->name('dashboard');

// ======================
// Language
// ======================
Route::get('/lang/{locale}', function (string $locale) {
    if (!in_array($locale, ['en', 'fr', 'ar'], true)) abort(400);
    session(['locale' => $locale]);
    return back();
})->name('lang.switch');

// ======================
// Authenticated (common)
// ======================
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::middleware('school.active')->group(function () {
        Route::get('/attendance/scan', [AttendanceScanController::class, 'index'])->name('attendance.scan.page');
        Route::get('/documents/{document}/download', DocumentDownloadController::class)
            ->whereNumber('document')
            ->name('documents.download');
        Route::get('/agenda/feed', [EventController::class, 'feed'])->name('agenda.feed');
        Route::get('/transport-ops', [TransportOpsController::class, 'index'])->name('transport.ops.index');
        Route::post('/transport-ops/logs', [TransportOpsController::class, 'store'])->name('transport.ops.store');
    });
});

require __DIR__ . '/auth.php';

// =====================================================================
// ADMIN (School Admin)
// =====================================================================
Route::prefix('admin')
    ->middleware(['auth', 'admin', 'school.active'])
    ->as('admin.')
    ->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Structure
        Route::get('/structure', [StructureController::class, 'index'])->name('structure.index');
        Route::post('/structure/levels', [StructureController::class, 'storeLevel'])->name('structure.levels.store');
        Route::put('/structure/levels/{level}', [StructureController::class, 'updateLevel'])->name('structure.levels.update');
        Route::delete('/structure/levels/{level}', [StructureController::class, 'destroyLevel'])->name('structure.levels.destroy');

        Route::post('/structure/classrooms', [StructureController::class, 'storeClassroom'])->name('structure.classrooms.store');
        Route::put('/structure/classrooms/{classroom}', [StructureController::class, 'updateClassroom'])->name('structure.classrooms.update');
        Route::delete('/structure/classrooms/{classroom}', [StructureController::class, 'destroyClassroom'])->name('structure.classrooms.destroy');
        Route::get('/structure/classrooms/{classroom}', [StructureController::class, 'showClassroom'])->name('structure.classrooms.show');

        // Students
        Route::resource('students', StudentController::class)->except(['show']);
        Route::get('/academic-years', [AdminAcademicYearController::class, 'index'])->name('academic-years.index');
        Route::get('/academic-years/create', [AdminAcademicYearController::class, 'create'])->name('academic-years.create');
        Route::post('/academic-years', [AdminAcademicYearController::class, 'store'])->name('academic-years.store');
        Route::post('/academic-years/{academicYear}/activate', [AdminAcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::post('/academic-years/{academicYear}/archive', [AdminAcademicYearController::class, 'archive'])->name('academic-years.archive');
        Route::get('/academic-promotions', [AdminStudentPromotionController::class, 'index'])->name('academic-promotions.index');
        Route::post('/academic-promotions', [AdminStudentPromotionController::class, 'store'])->name('academic-promotions.store');
        Route::post('/students/{student}/archive', [StudentController::class, 'archive'])->name('students.archive');
        Route::post('/students/{student}/reactivate', [StudentController::class, 'reactivate'])->name('students.reactivate');
        Route::get('/students/suggest', [StudentController::class, 'suggest'])->name('students.suggest');
        Route::get('/students/{student}/fees', [StudentFeePlanController::class, 'edit'])->name('students.fees.edit');
        Route::put('/students/{student}/fees', [StudentFeePlanController::class, 'update'])->name('students.fees.update');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

        // Finance
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/payments/create', [FinanceController::class, 'createPayment'])->name('finance.payments.create');
        Route::post('/finance/payments', [FinanceController::class, 'storePayment'])->name('finance.payments.store');
        Route::get('/finance/receipts/{receipt}', [FinanceController::class, 'showReceipt'])->name('finance.receipts.show');
        Route::get('/finance/receipts/{receipt}/export', [FinanceController::class, 'exportReceipt'])->name('finance.receipts.export');
        Route::get('/finance/suggest', [FinanceController::class, 'suggest'])->name('finance.suggest');
        Route::get('/finance/unpaid', [FinanceController::class, 'unpaid'])->name('finance.unpaid');
        Route::get('/finance/statement/print', [FinanceController::class, 'printStatement'])->name('finance.statement.print');
        Route::get('/finance/reminders', [AdminFinanceReminderController::class, 'edit'])->name('finance.reminders.edit');
        Route::put('/finance/reminders', [AdminFinanceReminderController::class, 'update'])->name('finance.reminders.update');
        Route::post('/finance/reminders/send-now', [AdminFinanceReminderController::class, 'sendNow'])->name('finance.reminders.send_now');

        Route::get('/parents/{parent}/students', [FinanceController::class, 'parentStudents'])->name('parents.students');
        Route::get('/parents/{parent}/students-with-fees', [FinanceController::class, 'parentStudentsWithFees'])
            ->name('parents.students_with_fees');
        Route::get('/parents', [ParentFeesController::class, 'index'])->name('parents.index');
        Route::get('/parents/{parent}/fees', [ParentFeesController::class, 'edit'])->name('parents.fees.edit');
        Route::put('/parents/{parent}/fees', [ParentFeesController::class, 'update'])->name('parents.fees.update');

        // Users
        Route::resource('users', UserController::class);
        Route::get('/users/suggest', [UserController::class, 'suggest'])->name('users.suggest');
        Route::get('/users/suggest-parents', [UserController::class, 'suggestParents'])->name('users.suggest_parents');

        Route::prefix('cards')->as('cards.')->group(function () {
            Route::get('/', [CardController::class, 'adminIndex'])->name('index');
            Route::get('/students/{student}', [CardController::class, 'adminShowStudent'])->name('students.show');
            Route::post('/students/{student}/regenerate', [CardController::class, 'regenerateStudent'])->name('students.regenerate');
            Route::get('/parents/{user}', [CardController::class, 'adminShowParent'])->name('parents.show');
            Route::post('/parents/{user}/regenerate', [CardController::class, 'regenerateParent'])->name('parents.regenerate');
        });

        Route::prefix('calendar')->as('calendar.')->group(function () {
            Route::get('/', [CalendarController::class, 'adminIndex'])->name('index');
            Route::get('/create', [CalendarController::class, 'create'])->name('create');
            Route::post('/', [CalendarController::class, 'store'])->name('store');
            Route::get('/{event}/edit', [CalendarController::class, 'edit'])->name('edit');
            Route::put('/{event}', [CalendarController::class, 'update'])->name('update');
            Route::delete('/{event}', [CalendarController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('agenda')->as('events.')->group(function () {
            Route::get('/', [EventController::class, 'adminIndex'])->name('index');
            Route::get('/create', [EventController::class, 'create'])->name('create');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
            Route::put('/{event}', [EventController::class, 'update'])->name('update');
            Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
        });

        Route::resource('activities', AdminActivityController::class)->except(['show']);

        // Subjects
        Route::resource('subjects', SubjectController::class)->except(['show']);

        // Courses / Homeworks
        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [AdminCourseController::class, 'store'])->name('courses.store');
        Route::get('/homeworks', [AdminHomeworkController::class, 'index'])->name('homeworks.index');
        Route::get('/homeworks/create', [AdminHomeworkController::class, 'create'])->name('homeworks.create');
        Route::post('/homeworks', [AdminHomeworkController::class, 'store'])->name('homeworks.store');
        Route::get('/homeworks/{homework}', [AdminHomeworkController::class, 'show'])->name('homeworks.show');
        Route::get('/homeworks/{homework}/edit', [AdminHomeworkController::class, 'edit'])->name('homeworks.edit');
        Route::put('/homeworks/{homework}', [AdminHomeworkController::class, 'update'])->name('homeworks.update');
        Route::delete('/homeworks/{homework}', [AdminHomeworkController::class, 'destroy'])->name('homeworks.destroy');
        Route::post('/homeworks/{homework}/approve', [AdminHomeworkController::class, 'approve'])->name('homeworks.approve');
        Route::post('/homeworks/{homework}/reject', [AdminHomeworkController::class, 'reject'])->name('homeworks.reject');
        Route::get('/homeworks/attachments/{attachment}', [AdminHomeworkController::class, 'downloadAttachment'])->name('homeworks.attachments.download');
        Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/courses/{course}/approve', [AdminCourseController::class, 'approve'])->name('courses.approve');
        Route::post('/courses/{course}/reject', [AdminCourseController::class, 'reject'])->name('courses.reject');
        Route::get('/timetable/settings', [AdminTimetableSettingsController::class, 'edit'])->name('timetable.settings.edit');
        Route::put('/timetable/settings', [AdminTimetableSettingsController::class, 'update'])->name('timetable.settings.update');
        Route::put('/timetable/{timetable}/move', [AdminTimetableController::class, 'updateTimePosition'])->name('timetable.move');
        Route::resource('timetable', AdminTimetableController::class)->except(['show']);
        Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/open', [NotificationCenterController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read_all');
        Route::post('/notifications/push-test', [AdminPushTestController::class, 'store'])->name('notifications.push_test');
        Route::prefix('documents/registration-requirements')->as('documents.registration-requirements.')->group(function () {
            Route::get('/', [RegistrationRequirementController::class, 'index'])->name('index');
            Route::post('/', [RegistrationRequirementController::class, 'store'])->name('store');
            Route::put('/{item}', [RegistrationRequirementController::class, 'update'])->name('update');
            Route::delete('/{item}', [RegistrationRequirementController::class, 'destroy'])->name('destroy');
            Route::post('/{item}/move/{direction}', [RegistrationRequirementController::class, 'move'])->name('move');
            Route::get('/preview', [RegistrationRequirementController::class, 'preview'])->name('preview');
            Route::get('/pdf', [RegistrationRequirementController::class, 'pdf'])->name('pdf');
        });
        Route::get('/documents/library', [DocumentLibraryController::class, 'index'])->name('documents.library.index');
        Route::post('/documents/library', [DocumentLibraryController::class, 'store'])->name('documents.library.store');
        Route::put('/documents/library/{document}', [DocumentLibraryController::class, 'update'])->name('documents.library.update');
        Route::delete('/documents/library/{document}', [DocumentLibraryController::class, 'destroy'])->name('documents.library.destroy');

        // News / Appointments / School Life
        Route::resource('news', AdminNewsController::class)->except(['show']);
        Route::resource('appointments', AdminAppointmentController::class)->except(['show']);
        Route::get('/appointments/{appointment}', [AdminAppointmentController::class, 'show'])->name('appointments.show');
        Route::post('/appointments/{appointment}/approve', [AdminAppointmentController::class, 'approve'])->name('appointments.approve');
        Route::post('/appointments/{appointment}/reject', [AdminAppointmentController::class, 'reject'])->name('appointments.reject');
        Route::resource('school-life', AdminSchoolLifeController::class)->except(['show']);

        // Teachers pedagogy
        Route::get('/teachers/pedagogy', [TeacherPedagogyController::class, 'index'])->name('teachers.pedagogy');
        Route::put('/teachers/{teacher}/pedagogy', [TeacherPedagogyController::class, 'update'])->name('teachers.pedagogy.update');
        Route::post('/teachers/{teacher}/pedagogy', [TeacherPedagogyController::class, 'update'])
            ->name('teachers.pedagogy.update_post'); // compat
        Route::post('/teachers/{teacher}/pedagogy/resources', [TeacherPedagogyController::class, 'storeResource'])->name('teachers.pedagogy.resources.store');
        Route::delete('/teachers/{teacher}/pedagogy/resources/{resource}', [TeacherPedagogyController::class, 'destroyResource'])->name('teachers.pedagogy.resources.destroy');

        // ======================
        // Messages (Admin)
        // ======================
        Route::prefix('messages')->as('messages.')->group(function () {
            Route::get('/',        [AdminMessageController::class, 'index'])->name('index');
            Route::get('/pending', [AdminMessageController::class, 'pending'])->name('pending');

            Route::get('/create',  [AdminMessageController::class, 'create'])->name('create');
            Route::post('/',       [AdminMessageController::class, 'store'])->name('store');

            Route::get('/{message}', [AdminMessageController::class, 'show'])
                ->whereNumber('message')->name('show');

            Route::post('/{message}/approve', [AdminMessageController::class, 'approve'])
                ->whereNumber('message')->name('approve');

            Route::post('/{message}/reject', [AdminMessageController::class, 'reject'])
                ->whereNumber('message')->name('reject');
        });

        // ======================
        // Transport Management
        // ======================
        Route::prefix('transport')->as('transport.')->group(function () {
            // Use controller to provide required variables to the view
            Route::get('/', [TransportController::class, 'index'])->name('index');
            Route::resource('vehicles', VehicleController::class);
            Route::resource('routes', RouteController::class);
            Route::post('routes/{route}/assign-students', [RouteController::class, 'assignStudents'])->name('routes.assign-students');
            Route::resource('assignments', TransportAssignmentController::class)->parameters(['assignments' => 'transportAssignment']);
        });
    });

// =====================================================================
// SUPER ADMIN
// =====================================================================
Route::prefix('super')
    ->middleware(['auth', 'super_admin'])
    ->as('super.')
    ->group(function () {

        Route::get('/dashboard', [SuperDashboardController::class, 'index'])->name('dashboard');

        Route::get('/schools', [SuperSchoolController::class, 'index'])->name('schools.index');
        Route::get('/schools/create', [SuperSchoolController::class, 'create'])->name('schools.create');
        Route::post('/schools', [SuperSchoolController::class, 'store'])->name('schools.store');

        Route::get('/schools/{school}/edit', [SuperSchoolController::class, 'edit'])->name('schools.edit');
        Route::put('/schools/{school}', [SuperSchoolController::class, 'update'])->name('schools.update');
        Route::post('/schools/{school}/toggle', [SuperSchoolController::class, 'toggleActive'])->name('schools.toggle');

        Route::delete('/schools/{school}', [SuperSchoolController::class, 'destroy'])->name('schools.destroy');
    });

// =====================================================================
// TEACHER
// =====================================================================
Route::prefix('teacher')
    ->middleware(['auth', 'teacher', 'school.active'])
    ->as('teacher.')
    ->group(function () {

        Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');

        // Courses
        Route::prefix('courses')->as('courses.')->group(function () {
            Route::get('/', [TeacherCourseController::class, 'index'])->name('index');
            Route::get('/create', [TeacherCourseController::class, 'create'])->name('create');
            Route::post('/', [TeacherCourseController::class, 'store'])->name('store');
        });

        // Homeworks
        Route::prefix('homeworks')->as('homeworks.')->group(function () {
            Route::get('/', [TeacherHomeworkController::class, 'index'])->name('index');
            Route::get('/create', [TeacherHomeworkController::class, 'create'])->name('create');
            Route::post('/', [TeacherHomeworkController::class, 'store'])->name('store');
        });

        // Assessments
        Route::prefix('assessments')->as('assessments.')->group(function () {
            Route::get('/', [TeacherAssessmentsController::class, 'index'])->name('index');
            Route::get('/create', [TeacherAssessmentsController::class, 'create'])->name('create');
            Route::post('/', [TeacherAssessmentsController::class, 'store'])->name('store');
        });

        // Grades
        Route::prefix('grades')->as('grades.')->group(function () {
            Route::get('/', [TeacherGradesController::class, 'index'])->name('index');
            Route::post('/', [TeacherGradesController::class, 'store'])->name('store');
        });

        // Attendance
        Route::prefix('attendance')->as('attendance.')->group(function () {
            Route::get('/', [TeacherAttendanceController::class, 'index'])->name('index');
            Route::post('/', [TeacherAttendanceController::class, 'store'])->name('store');
        });

        Route::get('/timetable', [TeacherTimetableController::class, 'index'])->name('timetable.index');
        Route::get('/calendar', [CalendarController::class, 'teacherIndex'])->name('calendar.index');
        Route::get('/agenda', [EventController::class, 'teacherIndex'])->name('events.index');
        Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/open', [NotificationCenterController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read_all');

        // ======================
        // Messages (Teacher)
        // ======================
        Route::prefix('messages')->as('messages.')->group(function () {
            Route::get('/',       [TeacherMessageController::class, 'index'])->name('index');
            Route::get('/create', [TeacherMessageController::class, 'create'])->name('create');
            Route::post('/',      [TeacherMessageController::class, 'store'])->name('store');

            Route::get('/{message}', [TeacherMessageController::class, 'show'])
                ->whereNumber('message')->name('show');
        });
    });

// =====================================================================
// PARENT
// =====================================================================
Route::prefix('parent')
    ->middleware(['auth', 'parent', 'school.active'])
    ->as('parent.')
    ->group(function () {

        Route::get('/', [ParentDashboardController::class, 'index'])->name('dashboard');

        Route::get('/courses', [ParentCoursesController::class, 'index'])->name('courses.index');
        Route::get('/homeworks', [ParentHomeworksController::class, 'index'])->name('homeworks.index');
        Route::get('/children', [ParentChildrenController::class, 'index'])->name('children.index');
        Route::get('/children/{student}/courses', [ParentCoursesController::class, 'childCourses'])->name('children.courses');
        Route::get('/children/{student}/homeworks', [ParentHomeworksController::class, 'childHomeworks'])->name('children.homeworks');
        Route::get('/children/{student}/timetable', [ParentTimetableController::class, 'childTimetable'])->name('children.timetable');
        Route::get('/grades', [ParentGradesController::class, 'index'])->name('grades.index');
        Route::get('/attendance', [ParentAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/activities', [ParentActivityController::class, 'index'])->name('activities.index');
        Route::post('/activities/{activity}/confirm', [ParentActivityController::class, 'confirm'])->name('activities.confirm');
        Route::get('/transport', [ParentTransportController::class, 'index'])->name('transport.index');
        Route::get('/pickup-requests', [ParentPickupRequestController::class, 'index'])->name('pickup-requests.index');
        Route::get('/pickup-requests/create', [ParentPickupRequestController::class, 'create'])->name('pickup-requests.create');
        Route::post('/pickup-requests', [ParentPickupRequestController::class, 'store'])->name('pickup-requests.store');
        Route::get('/cards', [CardController::class, 'parentIndex'])->name('cards.index');
        Route::get('/cards/self', [CardController::class, 'parentShowSelf'])->name('cards.self');
        Route::get('/cards/children/{student}', [CardController::class, 'parentShowStudent'])->name('cards.children.show');
        Route::get('/agenda', [EventController::class, 'parentIndex'])->name('events.index');
        Route::get('/calendar', [CalendarController::class, 'parentIndex'])->name('calendar.index');
        Route::get('/finance', [ParentFinanceController::class, 'index'])->name('finance.index');
        Route::get('/children/{student}/grades', [ParentGradesController::class, 'childGrades'])->name('children.grades');
        Route::get('/children/{student}/attendance', [ParentAttendanceController::class, 'childAttendance'])->name('children.attendance');
        Route::get('/children/{student}/finance', [ParentFinanceController::class, 'childFinance'])->name('children.finance');
        Route::get('/finance/receipts/{receipt}', [ParentFinanceController::class, 'showReceipt'])->name('finance.receipts.show');
        Route::get('/finance/receipts/{receipt}/export', [ParentFinanceController::class, 'exportReceipt'])->name('finance.receipts.export');

        Route::get('/courses/attachments/{attachment}', [ParentCoursesController::class, 'download'])->name('courses.attachments.download');
        Route::get('/homeworks/attachments/{attachment}', [ParentHomeworksController::class, 'download'])->name('homeworks.attachments.download');
        Route::get('/appointments', [ParentAppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/create', [ParentAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [ParentAppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/notifications', [ParentNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/open', [ParentNotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/{notification}/read', [ParentNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read_all');
        // ======================
        // Messages (Parent)
        // ======================
        Route::prefix('messages')->as('messages.')->group(function () {
            Route::get('/',       [ParentMessageController::class, 'index'])->name('index');
            Route::get('/create', [ParentMessageController::class, 'create'])->name('create');
            Route::post('/',      [ParentMessageController::class, 'store'])->name('store');

            Route::get('/{message}', [ParentMessageController::class, 'show'])
                ->whereNumber('message')->name('show');
        });
    });

// =====================================================================
// SCHOOL LIFE / RESPONSABLE SCOLAIRE
// =====================================================================
Route::prefix('school-life')
    ->middleware(['auth', 'school_life', 'school.active'])
    ->as('school-life.')
    ->group(function () {
        Route::get('/', [SchoolLifeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/students', [SchoolLifeStudentsController::class, 'index'])->name('students.index');
        Route::get('/students/{student}/behaviors', [SchoolLifeBehaviorController::class, 'show'])->name('students.behaviors.show');
        Route::post('/students/{student}/behaviors', [SchoolLifeBehaviorController::class, 'store'])->name('students.behaviors.store');
        Route::put('/students/{student}/behaviors/{behavior}', [SchoolLifeBehaviorController::class, 'update'])->name('students.behaviors.update');
        Route::delete('/students/{student}/behaviors/{behavior}', [SchoolLifeBehaviorController::class, 'destroy'])->name('students.behaviors.destroy');
        Route::get('/cards', [CardController::class, 'schoolLifeIndex'])->name('cards.index');
        Route::get('/cards/students/{student}', [CardController::class, 'schoolLifeShowStudent'])->name('cards.students.show');
        Route::get('/cards/parents/{user}', [CardController::class, 'schoolLifeShowParent'])->name('cards.parents.show');
        Route::get('/attendance', [SchoolLifeAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/manual', [SchoolLifeAttendanceController::class, 'manual'])->name('attendance.manual');
        Route::post('/attendance/manual', [SchoolLifeAttendanceController::class, 'storeManual'])->name('attendance.manual.store');
        Route::get('/attendance/export', [SchoolLifeAttendanceController::class, 'export'])->name('attendance.export');
        Route::get('/attendance/scan', [SchoolLifeAttendanceScanController::class, 'index'])->name('qr-scan.index');
        Route::post('/attendance/scan', [SchoolLifeAttendanceScanController::class, 'store'])->name('qr-scan.store');
        Route::get('/attendance/scan-records/{attendance}/edit', [SchoolLifeAttendanceScanController::class, 'edit'])->name('qr-scan.records.edit');
        Route::put('/attendance/scan-records/{attendance}', [SchoolLifeAttendanceScanController::class, 'update'])->name('qr-scan.records.update');
        Route::get('/grades', [SchoolLifeGradesController::class, 'index'])->name('grades.index');
        Route::get('/homeworks', [SchoolLifeHomeworkController::class, 'index'])->name('homeworks.index');
        Route::get('/homeworks/{homework}', [SchoolLifeHomeworkController::class, 'show'])->name('homeworks.show');
        Route::get('/homeworks/{homework}/edit', [SchoolLifeHomeworkController::class, 'edit'])->name('homeworks.edit');
        Route::put('/homeworks/{homework}', [SchoolLifeHomeworkController::class, 'update'])->name('homeworks.update');
        Route::delete('/homeworks/{homework}', [SchoolLifeHomeworkController::class, 'destroy'])->name('homeworks.destroy');
        Route::post('/homeworks/{homework}/approve', [SchoolLifeHomeworkController::class, 'approve'])->name('homeworks.approve');
        Route::post('/homeworks/{homework}/reject', [SchoolLifeHomeworkController::class, 'reject'])->name('homeworks.reject');
        Route::get('/homeworks/attachments/{attachment}', [SchoolLifeHomeworkController::class, 'downloadAttachment'])->name('homeworks.attachments.download');
        Route::get('/activities', [SchoolLifeActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/{activity}', [SchoolLifeActivityController::class, 'show'])->name('activities.show');
        Route::post('/activities/{activity}/participants/{participant}', [SchoolLifeActivityController::class, 'updateParticipant'])->name('activities.participants.update');
        Route::post('/activities/{activity}/reports', [SchoolLifeActivityController::class, 'storeReport'])->name('activities.reports.store');
        Route::get('/calendar', [CalendarController::class, 'schoolLifeIndex'])->name('calendar.index');
        Route::get('/agenda', [EventController::class, 'schoolLifeIndex'])->name('events.index');
        Route::get('/pickup-requests', [SchoolLifePickupRequestController::class, 'index'])->name('pickup-requests.index');
        Route::post('/pickup-requests/{pickupRequest}/approve', [SchoolLifePickupRequestController::class, 'approve'])->name('pickup-requests.approve');
        Route::post('/pickup-requests/{pickupRequest}/reject', [SchoolLifePickupRequestController::class, 'reject'])->name('pickup-requests.reject');
        Route::post('/pickup-requests/{pickupRequest}/complete', [SchoolLifePickupRequestController::class, 'complete'])->name('pickup-requests.complete');
        Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/open', [NotificationCenterController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read_all');
        Route::prefix('documents/registration-requirements')->as('documents.registration-requirements.')->group(function () {
            Route::get('/', [RegistrationRequirementController::class, 'index'])->name('index');
            Route::post('/', [RegistrationRequirementController::class, 'store'])->name('store');
            Route::put('/{item}', [RegistrationRequirementController::class, 'update'])->name('update');
            Route::delete('/{item}', [RegistrationRequirementController::class, 'destroy'])->name('destroy');
            Route::post('/{item}/move/{direction}', [RegistrationRequirementController::class, 'move'])->name('move');
            Route::get('/preview', [RegistrationRequirementController::class, 'preview'])->name('preview');
            Route::get('/pdf', [RegistrationRequirementController::class, 'pdf'])->name('pdf');
        });
    });

// =====================================================================
// STUDENT
// =====================================================================
Route::prefix('student')
    ->middleware(['auth', 'student', 'school.active'])
    ->as('student.')
    ->group(function () {
        Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/courses', [StudentCoursesController::class, 'index'])->name('courses.index');
        Route::get('/courses/attachments/{attachment}', [StudentCoursesController::class, 'download'])->name('courses.attachments.download');
        Route::get('/homeworks', [StudentHomeworksController::class, 'index'])->name('homeworks.index');
        Route::get('/homeworks/attachments/{attachment}', [StudentHomeworksController::class, 'download'])->name('homeworks.attachments.download');
        Route::get('/card', [CardController::class, 'studentShow'])->name('card.show');
        Route::get('/activities', [StudentActivityController::class, 'index'])->name('activities.index');
        Route::get('/agenda', [EventController::class, 'studentIndex'])->name('events.index');
        Route::get('/calendar', [CalendarController::class, 'studentIndex'])->name('calendar.index');
        Route::get('/timetable', [StudentTimetableController::class, 'index'])->name('timetable.index');
        Route::get('/grades', [StudentGradesController::class, 'index'])->name('grades.index');
        Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/transport', [StudentTransportController::class, 'index'])->name('transport.index');
        Route::get('/finance', [StudentFinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/receipts/{receipt}', [StudentFinanceController::class, 'showReceipt'])->name('finance.receipts.show');
        Route::get('/finance/receipts/{receipt}/export', [StudentFinanceController::class, 'exportReceipt'])->name('finance.receipts.export');
        Route::get('/finance/receipts/{receipt}/pdf', [StudentFinanceController::class, 'downloadReceiptPdf'])->name('finance.receipts.pdf');
        Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/open', [NotificationCenterController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read_all');
    });

// =====================================================================
// DIRECTOR
// =====================================================================
Route::prefix('director')
    ->middleware(['auth', DirectorOnly::class, 'school.active'])
    ->as('director.')
    ->group(function () {

        Route::get('/', [DirectorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/monitoring', [DirectorMonitoringController::class, 'index'])->name('monitoring');

        Route::get('/teachers', [DirectorTeachersController::class, 'index'])->name('teachers.index');
        Route::post('/teachers/{teacher}/toggle', [DirectorTeachersController::class, 'toggleActive'])->name('teachers.toggle');
        Route::post('/teachers/{teacher}/assign', [DirectorTeachersController::class, 'assignClassrooms'])->name('teachers.assign');

        Route::get('/students', [DirectorStudentsController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [DirectorStudentsController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/notes', [DirectorStudentsController::class, 'storeNote'])->name('students.notes.store');
        Route::get('/attendance', [DirectorAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/calendar', [CalendarController::class, 'directorIndex'])->name('calendar.index');
        Route::get('/agenda', [EventController::class, 'directorIndex'])->name('events.index');

        Route::get('/parents', [DirectorParentsController::class, 'index'])->name('parents.index');

        Route::get('/exports/monthly.csv', [DirectorExportsController::class, 'monthlyCsv'])->name('exports.monthly_csv');

        // Results / Support / Fiche
        Route::get('/results', [DirectorResultsController::class, 'index'])->name('results.index');
        Route::get('/students/{student}/fiche', [DirectorStudentFicheController::class, 'show'])->name('students.fiche');
        Route::get('/support', [DirectorSupportController::class, 'index'])->name('support.index');

        // Councils
        Route::get('/councils', [DirectorCouncilController::class, 'index'])->name('councils.index');
        Route::get('/councils/create', [DirectorCouncilController::class, 'create'])->name('councils.create');
        Route::post('/councils', [DirectorCouncilController::class, 'store'])->name('councils.store');

        // Reports
        Route::get('/reports', [DirectorReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/create', [DirectorReportsController::class, 'create'])->name('reports.create');
        Route::post('/reports', [DirectorReportsController::class, 'store'])->name('reports.store');
        Route::prefix('documents/registration-requirements')->as('documents.registration-requirements.')->group(function () {
            Route::get('/', [RegistrationRequirementController::class, 'index'])->name('index');
            Route::post('/', [RegistrationRequirementController::class, 'store'])->name('store');
            Route::put('/{item}', [RegistrationRequirementController::class, 'update'])->name('update');
            Route::delete('/{item}', [RegistrationRequirementController::class, 'destroy'])->name('destroy');
            Route::post('/{item}/move/{direction}', [RegistrationRequirementController::class, 'move'])->name('move');
            Route::get('/preview', [RegistrationRequirementController::class, 'preview'])->name('preview');
            Route::get('/pdf', [RegistrationRequirementController::class, 'pdf'])->name('pdf');
        });

        // Director messages
        Route::prefix('messages')->as('messages.')->group(function () {
            Route::get('/', [DirectorMessageController::class, 'index'])->name('index');
            Route::get('/{message}', [DirectorMessageController::class, 'show'])
                ->whereNumber('message')->name('show');
        });
    });
