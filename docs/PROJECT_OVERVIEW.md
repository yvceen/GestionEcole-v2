# Project Overview (Owner Level)

Last audited: 2026-02-19

## Latest modification snapshot (2026-02-19)

- Multi-school subdomain readiness hardened:
  - `SetCurrentSchool` resolves school from host using `APP_BASE_DOMAINS` (`{slug}.myedu.test`, `{slug}.myedu.school`).
  - Fallback to authenticated `user.school_id` preserved for backward compatibility.
  - Non-super user safety: host-school mismatch returns 403.
  - School context enforcement applies only to school role spaces (`admin|teacher|parent|student|director`) to avoid breaking auth/profile/public routes.
  - Shared bindings available to all views: `currentSchool`, `current_school`, `current_school_id`.
- Schools schema guard migration added:
  - `2026_02_19_233000_ensure_schools_slug_and_logo_columns.php`
  - ensures `slug` + `logo_path`, backfills unique slugs, enforces unique slug index.
- Unified navbar branding consistency:
  - Shared navbar keeps single component architecture.
  - Always shows MyEdu logo + label, plus school logo/name when available.
  - Mobile sidebar click issue fixed by moving toggle state ownership to `app-shell`.
- Session/subdomain deployment guidance:
  - `.env.example` documents `APP_BASE_DOMAINS` and `SESSION_DOMAIN` recommendations for local/prod.

- Navbar mobile hamburger fix:
  - Root cause: logique de toggle mobile implementee dans la navbar via style inline sur le sidebar, entrainant un comportement non fiable.
  - Fix: gestion d'etat mobile du sidebar deplacee dans `x-app-shell` avec events Alpine depuis `x-app-navbar`.
  - UX: overlay mobile, fermeture click-outside, fermeture ESC, accessibilite `aria-expanded/aria-controls`.
  - Files: `resources/views/components/app-shell.blade.php`, `resources/views/components/app-navbar.blade.php`.

- Unified Navbar System (single component) deployed:
  - source of truth: `resources/views/components/app-navbar.blade.php`
  - shared shell integration: `resources/views/components/app-shell.blade.php`
  - all role layouts using `x-app-shell` now render exactly the same navbar UI/behavior
  - dynamic school branding (logo + school name) based on current school context
  - route-safe role quick links + notifications + profile/logout controls
- Current school context API harmonized:
  - middleware now binds both `current_school` (legacy) and `currentSchool` aliases

- P0 fixed for teacher homework 500:
  - Root cause in logs: `SQLSTATE[HY000]: Field 'school_id' doesn't have a default value` during insert into `homework_attachments`.
  - Fix applied in `Teacher\\HomeworkController@store` to include `school_id` when the column exists.
- Homework admin-approval workflow is now complete:
  - Teacher create => `pending`
  - Admin routes: `admin.homeworks.index/show/approve/reject`
  - Admin navigation includes visible sidebar entry `Devoirs` + dashboard shortcut card for pending items
  - Admin table improved (search, status filter, pending-first, stats, actions)
  - Parent/student visibility is restricted to approved homeworks (schema-aware fallback preserved)
- Multi-role notifications upgraded with strict recipient targeting:
  - `notifications` supports `recipient_user_id` + `recipient_role` (backward-compatible with legacy `user_id`)
  - Unified service `NotificationService::notifyUsers(...)` for bulk targeted delivery (no fallback broadcast)
  - Connected triggers:
    - teacher homework submit -> admins of same school only (pending alert)
    - appointments approve/reject -> requesting parent only
    - messages -> exact targeted users or classroom parents only
    - homeworks/courses approve -> parents + students + owner teacher in scope
    - homeworks/courses reject -> owner teacher only
    - news scope classroom/school -> only parents/students in selected scope
- Notification bell/pages are available for all core roles:
  - `/admin/notifications`
  - `/teacher/notifications`
  - `/student/notifications`
  - `/parent/notifications`

- Parent Notification System added with strict targeting (no implicit/global broadcast):
  - Notification storage table (`notifications`) now persists one row per parent recipient
  - Shared service `App\Services\NotificationService` handles recipient filtering + bulk inserts
  - Connected modules:
    - appointments approve/reject
    - admin messages (direct parent and classroom targeting)
    - homeworks (admin approval only)
    - courses (admin approval only)
    - news (scope: classroom default, or school)
  - Parent bell + notifications page added (`/parent/notifications`)
- Rendez-vous admin workflow upgraded:
  - Parent request creation remains at `/parent/appointments/create` -> `POST /parent/appointments`
  - Admin review actions added: `GET /admin/appointments/{appointment}`, `POST /admin/appointments/{appointment}/approve`, `POST /admin/appointments/{appointment}/reject`
  - Status normalization hardened for legacy values (`draft/confirmed/archived`) to canonical `pending/approved/rejected`
  - Admin table refreshed with search + status filters + pending-first ordering + quick stats cards
  - Review metadata migration added: `approved_at`, `approved_by`, `rejected_at`, `rejected_by`
- Timetable module is now active across roles:
  - Admin CRUD + planning settings + drag/drop move endpoint (`/admin/timetable`, `/admin/timetable/settings`, `PUT /admin/timetable/{timetable}/move`)
  - Student read view (`/student/timetable`)
  - Parent child view (`/parent/children/{student}/timetable`)
  - Teacher read view (`/teacher/timetable`)
- Messaging controllers were hardened for schema variants (`target_*` vs `recipient_*`) to avoid SQL unknown-column 500 errors.
- Route inventory refreshed from runtime (`php artisan route:list --json`).

## 1) What this project is

### Stack and versions (verified from code/runtime)
- Backend framework: Laravel 12 (`laravel/framework ^12.0`, runtime `Laravel Framework 12.47.0`)
- PHP: 8.2 (`composer.json` requires `^8.2`; local CLI is `8.2.12`)
- Frontend build: Vite 7 + Tailwind CSS 3 + Alpine.js
- UI layer: Blade templates (not React/Vue SPA)
- Auth starter: Laravel Breeze (package present)
- Mobile wrapper present: Capacitor (Android folder + `@capacitor/*` packages)

### Architecture
- Type: Laravel monolith (single app for backend + frontend)
- Backend location: `app/`, `routes/`, `database/`, `config/`
- Frontend location: Blade in `resources/views`, CSS/JS in `resources/css` and `resources/js`, built by Vite
- No separate API service found (`routes/api.php` is absent)

### Local run entry points
- HTTP app entry: `public/index.php`
- Main web routes file: `routes/web.php`
- Local dev startup:
1. `php artisan serve`
2. `npm run dev`

## 2) High-level structure

### Main folders
- `app/`: application code (controllers, models, middleware, providers)
- `bootstrap/`: Laravel bootstrap and middleware alias wiring
- `config/`: framework/service configuration (`auth`, `database`, `queue`, etc.)
- `database/`: migrations, factories, seeders
- `resources/`: Blade views, frontend assets
- `routes/`: route definitions (`web.php`, `auth.php`, `console.php`)
- `public/`: web root and static public assets
- `tests/`: PHPUnit feature/unit tests
- `android/`: Capacitor Android project wrapper
- `docs/`: project-level documentation and progress tracking

### Most important areas
- Routes:
  - `routes/web.php`: all role portals and business routes
  - `routes/auth.php`: login/register/password/email verification routes
- Controllers:
  - `app/Http/Controllers/Admin/*`
  - `app/Http/Controllers/Teacher/*`
  - `app/Http/Controllers/Parent/*`
  - `app/Http/Controllers/Student/*`
  - `app/Http/Controllers/Director/*`
  - `app/Http/Controllers/SuperAdmin/*`
- Models:
  - Core business models in `app/Models` (`Student`, `User`, `Course`, `Homework`, `Payment`, etc.)
- Middleware:
  - Role gates: `AdminOnly`, `DirectorOnly`, `TeacherOnly`, `ParentOnly`, `StudentOnly`, `SuperAdmin`
  - Context: `SetCurrentSchool`, `SetLocale`
- Views/components:
  - Role layouts: `resources/views/components/*-layout.blade.php`
  - Domain views under `resources/views/admin`, `teacher`, `parent`, `student`, `director`, `super`
- Database:
  - Migrations: `database/migrations` (80+ historical migrations)
  - Seeders: `database/seeders`

## 3) End-to-end system behavior

### Authentication flow
- Guard: `web` session guard (`config/auth.php`)
- Login request validation and rate-limiting: `app/Http/Requests/Auth/LoginRequest.php`
- On successful login:
  - Session is regenerated
  - Redirect is role-based in `AuthenticatedSessionController::store()`:
    - `super_admin -> /super/dashboard`
    - `admin -> /admin`
    - `director -> /director`
    - `teacher -> /teacher`
    - `parent -> /parent`
    - `student -> /student`
- Session storage:
  - `.env.example` defaults to `SESSION_DRIVER=database`
  - Uses `sessions` table
- Token model:
  - No JWT/Sanctum API auth flow currently wired for app endpoints

### Authorization / roles / permissions
- Primary authorization style: route-group middleware by role
- Middleware aliases configured in `bootstrap/app.php`
- Roles in `User` model constants:
  - `admin`, `director`, `teacher`, `parent`, `student`, `super_admin`, `chauffeur`
- Multi-school scoping:
  - Many models use `BelongsToSchool` trait global scope
  - `SetCurrentSchool` middleware binds current school context for requests

### Main modules/features
- Admin:
  - Students, users, structure (levels/classrooms), finance, subjects, transport, messaging
  - Timetable management (weekly schedule + settings + move/resize support)
  - Content modules: news, appointments, school-life
  - Appointments module: review workflow (`pending -> approved/rejected`) with admin timestamps/actor tracking
  - Targeted parent notifications wired on appointments/messages/homeworks/courses/news events
  - Courses/homeworks creation endpoints exist
- Teacher:
  - Courses, homeworks, assessments, grades, attendance, messaging, timetable (read)
- Parent:
  - Dashboard, courses/homeworks, attachments download, messaging, appointments
  - Child-specific routes for each linked student, including timetable
  - Appointment request form fields: `title`, `scheduled_at`, `parent_phone`, `message`
  - Notification center + bell:
    - `/parent/notifications`
    - `/parent/notifications/{notification}/open` (mark read on open)
- Student:
  - Dashboard, own classroom courses/homeworks, timetable
- Director:
  - Monitoring, teachers/students oversight, reports, councils, results, support, exports, messaging
- Super admin:
  - School management

### Database schema summary (live DB + migrations)

Core entities:
- `schools`: tenant/school records
- `users`: all actors (role column), optional `school_id`, `is_active`
- `students`: student profile (`school_id`, `classroom_id`, `parent_user_id`, optional `user_id` for student login)
- `classrooms`, `levels`: structure hierarchy per school

Learning content:
- `courses`, `course_attachments`, `course_files`
- `homeworks`, `homework_attachments`
- `subjects`, `teacher_subjects`, `classroom_subject`
- `assessments`, `grades`, `attendances`

Finance:
- `fee_items`, `student_fee_plans`, `parent_student_fees`
- `receipts`, `payments`, `payment_items`, `classroom_fees`

Messaging and operations:
- `messages`
- `appointments`, `news`, `school_lives`
- `transport_assignments`, `routes`, `route_stops`, `vehicles`
- `timetables`, `timetable_settings`
- Appointments review columns: `approved_at`, `approved_by`, `rejected_at`, `rejected_by`
- Parent notifications table: `notifications` (`user_id`, `type`, `title`, `body`, `data`, `read_at`)
- Notification targeting columns (backward-safe): `recipient_user_id`, `recipient_role`

Framework/system:
- `migrations`, `sessions`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks`, `password_reset_tokens`

Key relationships (from live foreign keys):
- `students.parent_user_id -> users.id`
- `students.user_id -> users.id`
- `students.classroom_id -> classrooms.id`
- `students.school_id -> schools.id`
- `classrooms.level_id -> levels.id`
- `courses.classroom_id -> classrooms.id`
- `courses.teacher_id -> users.id`
- `homeworks.classroom_id -> classrooms.id`
- `homeworks.teacher_id -> users.id`
- `parent_student_fees.student_id -> students.id`
- `payments.student_id -> students.id`
- `payments.receipt_id -> receipts.id`
- `transport_assignments.student_id -> students.id`
- `transport_assignments.route_id -> routes.id`
- `teacher_subjects.teacher_id -> users.id`
- `teacher_subjects.subject_id -> subjects.id`

## 4) All routes/endpoints

### API routes
- `routes/api.php` is not present
- No dedicated API endpoint set detected

### Most important routes (auth + core CRUD)
- Auth:
  - `GET /login`, `POST /login`, `POST /logout`
  - `GET /register`, `POST /register`
  - `GET /forgot-password`, `POST /forgot-password`
  - `GET /reset-password/{token}`, `POST /reset-password`
- Role dashboards:
  - `GET /admin`, `/director`, `/teacher`, `/parent`, `/student`, `/super/dashboard`
- Core CRUD examples:
  - Students: `/admin/students` (`index/create/store/edit/update/destroy`)
  - Users: `/admin/users` (`index/create/store/edit/update/destroy`)
  - Subjects: `/admin/subjects` (`index/create/store/edit/update/destroy`)
  - Timetable: `/admin/timetable` (`index/create/store/edit/update/destroy/move`), `/admin/timetable/settings`
  - Appointments:
    - parent request: `/parent/appointments/create`, `POST /parent/appointments`
    - admin review: `/admin/appointments`, `/admin/appointments/{appointment}`, `POST /admin/appointments/{appointment}/approve`, `POST /admin/appointments/{appointment}/reject`
  - Parent notifications:
    - list: `/parent/notifications`
    - open+read: `/parent/notifications/{notification}/open`
    - explicit mark read: `POST /parent/notifications/{notification}/read`
  - Homework/Course approval endpoints for parent notifications:
    - `POST /admin/homeworks/{homework}/approve`
    - `POST /admin/courses/{course}/approve`
  - Transport: `/admin/transport/*` resource routes
  - News/Appointments/School-life: admin resource routes
  - Teacher content: `/teacher/courses`, `/teacher/homeworks`
  - Parent child views: `/parent/children/{student}/courses`, `/parent/children/{student}/homeworks`, `/parent/children/{student}/timetable`

### Route inventory summary
- Total routes discovered: 212
- Auth-protected routes: 194
- Guest-only routes: 8
- Prefix counts:
  - admin: 115
  - director: 21
  - teacher: 19
  - parent: 18
  - student: 4
  - super: 6

### Complete route table (web)
| Method | Path | Name | Action | Middleware |
|---|---|---|---|---|
| GET\\|HEAD | / | - | Closure | web |
| GET\\|HEAD | /_debugbar/assets | debugbar.assets | Fruitcake\LaravelDebugbar\Controllers\AssetController@getAssets | Fruitcake\LaravelDebugbar\Middleware\DebugbarEnabled, Closure |
| DELETE | /_debugbar/cache/{key} | debugbar.cache.delete | Fruitcake\LaravelDebugbar\Controllers\CacheController@delete | Fruitcake\LaravelDebugbar\Middleware\DebugbarEnabled, Closure |
| GET\\|HEAD | /_debugbar/clockwork/{id} | debugbar.clockwork | Fruitcake\LaravelDebugbar\Controllers\OpenHandlerController@clockwork | Fruitcake\LaravelDebugbar\Middleware\DebugbarEnabled, Closure |
| GET\\|HEAD | /_debugbar/open | debugbar.openhandler | Fruitcake\LaravelDebugbar\Controllers\OpenHandlerController@handle | Fruitcake\LaravelDebugbar\Middleware\DebugbarEnabled, Closure |
| POST | /_debugbar/queries/explain | debugbar.queries.explain | Fruitcake\LaravelDebugbar\Controllers\QueriesController@explain | Fruitcake\LaravelDebugbar\Middleware\DebugbarEnabled, Closure |
| GET\\|HEAD | /admin | admin.dashboard | App\Http\Controllers\Admin\DashboardController@index | web, auth, admin |
| GET\\|HEAD | /admin/appointments | admin.appointments.index | App\Http\Controllers\Admin\AppointmentController@index | web, auth, admin |
| POST | /admin/appointments | admin.appointments.store | App\Http\Controllers\Admin\AppointmentController@store | web, auth, admin |
| DELETE | /admin/appointments/{appointment} | admin.appointments.destroy | App\Http\Controllers\Admin\AppointmentController@destroy | web, auth, admin |
| PUT\\|PATCH | /admin/appointments/{appointment} | admin.appointments.update | App\Http\Controllers\Admin\AppointmentController@update | web, auth, admin |
| GET\\|HEAD | /admin/appointments/{appointment}/edit | admin.appointments.edit | App\Http\Controllers\Admin\AppointmentController@edit | web, auth, admin |
| GET\\|HEAD | /admin/appointments/create | admin.appointments.create | App\Http\Controllers\Admin\AppointmentController@create | web, auth, admin |
| GET\\|HEAD | /admin/courses | admin.courses.index | App\Http\Controllers\Admin\CourseController@index | web, auth, admin |
| POST | /admin/courses | admin.courses.store | App\Http\Controllers\Admin\CourseController@store | web, auth, admin |
| GET\\|HEAD | /admin/courses/create | admin.courses.create | App\Http\Controllers\Admin\CourseController@create | web, auth, admin |
| GET\\|HEAD | /admin/finance | admin.finance.index | App\Http\Controllers\Admin\FinanceController@index | web, auth, admin |
| POST | /admin/finance/payments | admin.finance.payments.store | App\Http\Controllers\Admin\FinanceController@storePayment | web, auth, admin |
| GET\\|HEAD | /admin/finance/payments/create | admin.finance.payments.create | App\Http\Controllers\Admin\FinanceController@createPayment | web, auth, admin |
| GET\\|HEAD | /admin/finance/receipts/{receipt} | admin.finance.receipts.show | App\Http\Controllers\Admin\FinanceController@showReceipt | web, auth, admin |
| GET\\|HEAD | /admin/finance/statement/print | admin.finance.statement.print | App\Http\Controllers\Admin\FinanceController@printStatement | web, auth, admin |
| GET\\|HEAD | /admin/finance/suggest | admin.finance.suggest | App\Http\Controllers\Admin\FinanceController@suggest | web, auth, admin |
| GET\\|HEAD | /admin/finance/unpaid | admin.finance.unpaid | App\Http\Controllers\Admin\FinanceController@unpaid | web, auth, admin |
| GET\\|HEAD | /admin/homeworks | admin.homeworks.index | App\Http\Controllers\Admin\HomeworkController@index | web, auth, admin |
| POST | /admin/homeworks | admin.homeworks.store | App\Http\Controllers\Admin\HomeworkController@store | web, auth, admin |
| GET\\|HEAD | /admin/homeworks/create | admin.homeworks.create | App\Http\Controllers\Admin\HomeworkController@create | web, auth, admin |
| GET\\|HEAD | /admin/messages | admin.messages.index | App\Http\Controllers\Admin\MessageController@index | web, auth, admin |
| POST | /admin/messages | admin.messages.store | App\Http\Controllers\Admin\MessageController@store | web, auth, admin |
| GET\\|HEAD | /admin/messages/{message} | admin.messages.show | App\Http\Controllers\Admin\MessageController@show | web, auth, admin |
| POST | /admin/messages/{message}/approve | admin.messages.approve | App\Http\Controllers\Admin\MessageController@approve | web, auth, admin |
| POST | /admin/messages/{message}/reject | admin.messages.reject | App\Http\Controllers\Admin\MessageController@reject | web, auth, admin |
| GET\\|HEAD | /admin/messages/create | admin.messages.create | App\Http\Controllers\Admin\MessageController@create | web, auth, admin |
| GET\\|HEAD | /admin/messages/pending | admin.messages.pending | App\Http\Controllers\Admin\MessageController@pending | web, auth, admin |
| GET\\|HEAD | /admin/news | admin.news.index | App\Http\Controllers\Admin\NewsController@index | web, auth, admin |
| POST | /admin/news | admin.news.store | App\Http\Controllers\Admin\NewsController@store | web, auth, admin |
| DELETE | /admin/news/{news} | admin.news.destroy | App\Http\Controllers\Admin\NewsController@destroy | web, auth, admin |
| PUT\\|PATCH | /admin/news/{news} | admin.news.update | App\Http\Controllers\Admin\NewsController@update | web, auth, admin |
| GET\\|HEAD | /admin/news/{news}/edit | admin.news.edit | App\Http\Controllers\Admin\NewsController@edit | web, auth, admin |
| GET\\|HEAD | /admin/news/create | admin.news.create | App\Http\Controllers\Admin\NewsController@create | web, auth, admin |
| GET\\|HEAD | /admin/parents | admin.parents.index | App\Http\Controllers\Admin\ParentFeesController@index | web, auth, admin |
| GET\\|HEAD | /admin/parents/{parent}/fees | admin.parents.fees.edit | App\Http\Controllers\Admin\ParentFeesController@edit | web, auth, admin |
| PUT | /admin/parents/{parent}/fees | admin.parents.fees.update | App\Http\Controllers\Admin\ParentFeesController@update | web, auth, admin |
| GET\\|HEAD | /admin/parents/{parent}/students | admin.parents.students | App\Http\Controllers\Admin\FinanceController@parentStudents | web, auth, admin |
| GET\\|HEAD | /admin/parents/{parent}/students-with-fees | admin.parents.students_with_fees | App\Http\Controllers\Admin\FinanceController@parentStudentsWithFees | web, auth, admin |
| GET\\|HEAD | /admin/school-life | admin.school-life.index | App\Http\Controllers\Admin\SchoolLifeController@index | web, auth, admin |
| POST | /admin/school-life | admin.school-life.store | App\Http\Controllers\Admin\SchoolLifeController@store | web, auth, admin |
| DELETE | /admin/school-life/{school_life} | admin.school-life.destroy | App\Http\Controllers\Admin\SchoolLifeController@destroy | web, auth, admin |
| PUT\\|PATCH | /admin/school-life/{school_life} | admin.school-life.update | App\Http\Controllers\Admin\SchoolLifeController@update | web, auth, admin |
| GET\\|HEAD | /admin/school-life/{school_life}/edit | admin.school-life.edit | App\Http\Controllers\Admin\SchoolLifeController@edit | web, auth, admin |
| GET\\|HEAD | /admin/school-life/create | admin.school-life.create | App\Http\Controllers\Admin\SchoolLifeController@create | web, auth, admin |
| GET\\|HEAD | /admin/structure | admin.structure.index | App\Http\Controllers\Admin\StructureController@index | web, auth, admin |
| POST | /admin/structure/classrooms | admin.structure.classrooms.store | App\Http\Controllers\Admin\StructureController@storeClassroom | web, auth, admin |
| DELETE | /admin/structure/classrooms/{classroom} | admin.structure.classrooms.destroy | App\Http\Controllers\Admin\StructureController@destroyClassroom | web, auth, admin |
| GET\\|HEAD | /admin/structure/classrooms/{classroom} | admin.structure.classrooms.show | App\Http\Controllers\Admin\StructureController@showClassroom | web, auth, admin |
| PUT | /admin/structure/classrooms/{classroom} | admin.structure.classrooms.update | App\Http\Controllers\Admin\StructureController@updateClassroom | web, auth, admin |
| POST | /admin/structure/levels | admin.structure.levels.store | App\Http\Controllers\Admin\StructureController@storeLevel | web, auth, admin |
| DELETE | /admin/structure/levels/{level} | admin.structure.levels.destroy | App\Http\Controllers\Admin\StructureController@destroyLevel | web, auth, admin |
| PUT | /admin/structure/levels/{level} | admin.structure.levels.update | App\Http\Controllers\Admin\StructureController@updateLevel | web, auth, admin |
| GET\\|HEAD | /admin/students | admin.students.index | App\Http\Controllers\Admin\StudentController@index | web, auth, admin |
| POST | /admin/students | admin.students.store | App\Http\Controllers\Admin\StudentController@store | web, auth, admin |
| DELETE | /admin/students/{student} | admin.students.destroy | App\Http\Controllers\Admin\StudentController@destroy | web, auth, admin |
| PUT\\|PATCH | /admin/students/{student} | admin.students.update | App\Http\Controllers\Admin\StudentController@update | web, auth, admin |
| GET\\|HEAD | /admin/students/{student}/edit | admin.students.edit | App\Http\Controllers\Admin\StudentController@edit | web, auth, admin |
| GET\\|HEAD | /admin/students/{student}/fees | admin.students.fees.edit | App\Http\Controllers\Admin\StudentFeePlanController@edit | web, auth, admin |
| PUT | /admin/students/{student}/fees | admin.students.fees.update | App\Http\Controllers\Admin\StudentFeePlanController@update | web, auth, admin |
| GET\\|HEAD | /admin/students/create | admin.students.create | App\Http\Controllers\Admin\StudentController@create | web, auth, admin |
| GET\\|HEAD | /admin/students/suggest | admin.students.suggest | App\Http\Controllers\Admin\StudentController@suggest | web, auth, admin |
| GET\\|HEAD | /admin/subjects | admin.subjects.index | App\Http\Controllers\Admin\SubjectController@index | web, auth, admin |
| POST | /admin/subjects | admin.subjects.store | App\Http\Controllers\Admin\SubjectController@store | web, auth, admin |
| DELETE | /admin/subjects/{subject} | admin.subjects.destroy | App\Http\Controllers\Admin\SubjectController@destroy | web, auth, admin |
| PUT\\|PATCH | /admin/subjects/{subject} | admin.subjects.update | App\Http\Controllers\Admin\SubjectController@update | web, auth, admin |
| GET\\|HEAD | /admin/subjects/{subject}/edit | admin.subjects.edit | App\Http\Controllers\Admin\SubjectController@edit | web, auth, admin |
| GET\\|HEAD | /admin/subjects/create | admin.subjects.create | App\Http\Controllers\Admin\SubjectController@create | web, auth, admin |
| POST | /admin/teachers/{teacher}/pedagogy | admin.teachers.pedagogy.update_post | App\Http\Controllers\Admin\TeacherPedagogyController@update | web, auth, admin |
| PUT | /admin/teachers/{teacher}/pedagogy | admin.teachers.pedagogy.update | App\Http\Controllers\Admin\TeacherPedagogyController@update | web, auth, admin |
| GET\\|HEAD | /admin/teachers/pedagogy | admin.teachers.pedagogy | App\Http\Controllers\Admin\TeacherPedagogyController@index | web, auth, admin |
| GET\\|HEAD | /admin/transport | admin.transport.index | App\Http\Controllers\Admin\TransportController@index | web, auth, admin |
| GET\\|HEAD | /admin/transport/assignments | admin.transport.assignments.index | App\Http\Controllers\Admin\TransportAssignmentController@index | web, auth, admin |
| POST | /admin/transport/assignments | admin.transport.assignments.store | App\Http\Controllers\Admin\TransportAssignmentController@store | web, auth, admin |
| DELETE | /admin/transport/assignments/{transportAssignment} | admin.transport.assignments.destroy | App\Http\Controllers\Admin\TransportAssignmentController@destroy | web, auth, admin |
| GET\\|HEAD | /admin/transport/assignments/{transportAssignment} | admin.transport.assignments.show | App\Http\Controllers\Admin\TransportAssignmentController@show | web, auth, admin |
| PUT\\|PATCH | /admin/transport/assignments/{transportAssignment} | admin.transport.assignments.update | App\Http\Controllers\Admin\TransportAssignmentController@update | web, auth, admin |
| GET\\|HEAD | /admin/transport/assignments/{transportAssignment}/edit | admin.transport.assignments.edit | App\Http\Controllers\Admin\TransportAssignmentController@edit | web, auth, admin |
| GET\\|HEAD | /admin/transport/assignments/create | admin.transport.assignments.create | App\Http\Controllers\Admin\TransportAssignmentController@create | web, auth, admin |
| GET\\|HEAD | /admin/transport/routes | admin.transport.routes.index | App\Http\Controllers\Admin\RouteController@index | web, auth, admin |
| POST | /admin/transport/routes | admin.transport.routes.store | App\Http\Controllers\Admin\RouteController@store | web, auth, admin |
| DELETE | /admin/transport/routes/{route} | admin.transport.routes.destroy | App\Http\Controllers\Admin\RouteController@destroy | web, auth, admin |
| GET\\|HEAD | /admin/transport/routes/{route} | admin.transport.routes.show | App\Http\Controllers\Admin\RouteController@show | web, auth, admin |
| PUT\\|PATCH | /admin/transport/routes/{route} | admin.transport.routes.update | App\Http\Controllers\Admin\RouteController@update | web, auth, admin |
| GET\\|HEAD | /admin/transport/routes/{route}/edit | admin.transport.routes.edit | App\Http\Controllers\Admin\RouteController@edit | web, auth, admin |
| GET\\|HEAD | /admin/transport/routes/create | admin.transport.routes.create | App\Http\Controllers\Admin\RouteController@create | web, auth, admin |
| GET\\|HEAD | /admin/transport/vehicles | admin.transport.vehicles.index | App\Http\Controllers\Admin\VehicleController@index | web, auth, admin |
| POST | /admin/transport/vehicles | admin.transport.vehicles.store | App\Http\Controllers\Admin\VehicleController@store | web, auth, admin |
| DELETE | /admin/transport/vehicles/{vehicle} | admin.transport.vehicles.destroy | App\Http\Controllers\Admin\VehicleController@destroy | web, auth, admin |
| GET\\|HEAD | /admin/transport/vehicles/{vehicle} | admin.transport.vehicles.show | App\Http\Controllers\Admin\VehicleController@show | web, auth, admin |
| PUT\\|PATCH | /admin/transport/vehicles/{vehicle} | admin.transport.vehicles.update | App\Http\Controllers\Admin\VehicleController@update | web, auth, admin |
| GET\\|HEAD | /admin/transport/vehicles/{vehicle}/edit | admin.transport.vehicles.edit | App\Http\Controllers\Admin\VehicleController@edit | web, auth, admin |
| GET\\|HEAD | /admin/transport/vehicles/create | admin.transport.vehicles.create | App\Http\Controllers\Admin\VehicleController@create | web, auth, admin |
| GET\\|HEAD | /admin/users | admin.users.index | App\Http\Controllers\Admin\UserController@index | web, auth, admin |
| POST | /admin/users | admin.users.store | App\Http\Controllers\Admin\UserController@store | web, auth, admin |
| DELETE | /admin/users/{user} | admin.users.destroy | App\Http\Controllers\Admin\UserController@destroy | web, auth, admin |
| PUT\\|PATCH | /admin/users/{user} | admin.users.update | App\Http\Controllers\Admin\UserController@update | web, auth, admin |
| GET\\|HEAD | /admin/users/{user}/edit | admin.users.edit | App\Http\Controllers\Admin\UserController@edit | web, auth, admin |
| GET\\|HEAD | /admin/users/create | admin.users.create | App\Http\Controllers\Admin\UserController@create | web, auth, admin |
| GET\\|HEAD | /admin/users/suggest | admin.users.suggest | App\Http\Controllers\Admin\UserController@suggest | web, auth, admin |
| GET\\|HEAD | /admin/users/suggest-parents | admin.users.suggest_parents | App\Http\Controllers\Admin\UserController@suggestParents | web, auth, admin |
| GET\\|HEAD | /confirm-password | password.confirm | App\Http\Controllers\Auth\ConfirmablePasswordController@show | web, auth |
| POST | /confirm-password | - | App\Http\Controllers\Auth\ConfirmablePasswordController@store | web, auth |
| POST | /contact | contact.send | App\Http\Controllers\ContactController@send | web |
| GET\\|HEAD | /dashboard | dashboard | Closure | web, auth |
| GET\\|HEAD | /director | director.dashboard | App\Http\Controllers\Director\DashboardController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/councils | director.councils.index | App\Http\Controllers\Director\CouncilController@index | web, auth, App\Http\Middleware\DirectorOnly |
| POST | /director/councils | director.councils.store | App\Http\Controllers\Director\CouncilController@store | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/councils/create | director.councils.create | App\Http\Controllers\Director\CouncilController@create | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/exports/monthly.csv | director.exports.monthly_csv | App\Http\Controllers\Director\ExportsController@monthlyCsv | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/messages | director.messages.index | App\Http\Controllers\Director\MessageController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/messages/{message} | director.messages.show | App\Http\Controllers\Director\MessageController@show | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/monitoring | director.monitoring | App\Http\Controllers\Director\MonitoringController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/parents | director.parents.index | App\Http\Controllers\Director\ParentsController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/reports | director.reports.index | App\Http\Controllers\Director\ReportsController@index | web, auth, App\Http\Middleware\DirectorOnly |
| POST | /director/reports | director.reports.store | App\Http\Controllers\Director\ReportsController@store | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/reports/create | director.reports.create | App\Http\Controllers\Director\ReportsController@create | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/results | director.results.index | App\Http\Controllers\Director\ResultsController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/students | director.students.index | App\Http\Controllers\Director\StudentsController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/students/{student} | director.students.show | App\Http\Controllers\Director\StudentsController@show | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/students/{student}/fiche | director.students.fiche | App\Http\Controllers\Director\StudentFicheController@show | web, auth, App\Http\Middleware\DirectorOnly |
| POST | /director/students/{student}/notes | director.students.notes.store | App\Http\Controllers\Director\StudentsController@storeNote | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/support | director.support.index | App\Http\Controllers\Director\SupportController@index | web, auth, App\Http\Middleware\DirectorOnly |
| GET\\|HEAD | /director/teachers | director.teachers.index | App\Http\Controllers\Director\TeachersController@index | web, auth, App\Http\Middleware\DirectorOnly |
| POST | /director/teachers/{teacher}/assign | director.teachers.assign | App\Http\Controllers\Director\TeachersController@assignClassrooms | web, auth, App\Http\Middleware\DirectorOnly |
| POST | /director/teachers/{teacher}/toggle | director.teachers.toggle | App\Http\Controllers\Director\TeachersController@toggleActive | web, auth, App\Http\Middleware\DirectorOnly |
| POST | /email/verification-notification | verification.send | App\Http\Controllers\Auth\EmailVerificationNotificationController@store | web, auth, throttle:6,1 |
| GET\\|HEAD | /forgot-password | password.request | App\Http\Controllers\Auth\PasswordResetLinkController@create | web, guest |
| POST | /forgot-password | password.email | App\Http\Controllers\Auth\PasswordResetLinkController@store | web, guest |
| GET\\|HEAD | /lang/{locale} | lang.switch | Closure | web |
| GET\\|HEAD | /login | login | App\Http\Controllers\Auth\AuthenticatedSessionController@create | web, guest |
| POST | /login | - | App\Http\Controllers\Auth\AuthenticatedSessionController@store | web, guest |
| POST | /logout | logout | App\Http\Controllers\Auth\AuthenticatedSessionController@destroy | web, auth |
| GET\\|HEAD | /parent | parent.dashboard | App\Http\Controllers\Parent\DashboardController@index | web, auth, parent |
| POST | /parent/appointments | parent.appointments.store | App\Http\Controllers\Parent\AppointmentController@store | web, auth, parent |
| GET\\|HEAD | /parent/appointments/create | parent.appointments.create | App\Http\Controllers\Parent\AppointmentController@create | web, auth, parent |
| GET\\|HEAD | /parent/children | parent.children.index | App\Http\Controllers\Parent\ChildrenController@index | web, auth, parent |
| GET\\|HEAD | /parent/children/{student}/courses | parent.children.courses | App\Http\Controllers\Parent\CoursesController@childCourses | web, auth, parent |
| GET\\|HEAD | /parent/children/{student}/homeworks | parent.children.homeworks | App\Http\Controllers\Parent\HomeworkController@childHomeworks | web, auth, parent |
| GET\\|HEAD | /parent/courses | parent.courses.index | App\Http\Controllers\Parent\CoursesController@index | web, auth, parent |
| GET\\|HEAD | /parent/courses/attachments/{attachment} | parent.courses.attachments.download | App\Http\Controllers\Parent\CoursesController@download | web, auth, parent |
| GET\\|HEAD | /parent/homeworks | parent.homeworks.index | App\Http\Controllers\Parent\HomeworkController@index | web, auth, parent |
| GET\\|HEAD | /parent/homeworks/attachments/{attachment} | parent.homeworks.attachments.download | App\Http\Controllers\Parent\HomeworkController@download | web, auth, parent |
| GET\\|HEAD | /parent/messages | parent.messages.index | App\Http\Controllers\Parent\MessageController@index | web, auth, parent |
| POST | /parent/messages | parent.messages.store | App\Http\Controllers\Parent\MessageController@store | web, auth, parent |
| GET\\|HEAD | /parent/messages/{message} | parent.messages.show | App\Http\Controllers\Parent\MessageController@show | web, auth, parent |
| GET\\|HEAD | /parent/messages/create | parent.messages.create | App\Http\Controllers\Parent\MessageController@create | web, auth, parent |
| PUT | /password | password.update | App\Http\Controllers\Auth\PasswordController@update | web, auth |
| DELETE | /profile | profile.destroy | App\Http\Controllers\ProfileController@destroy | web, auth |
| GET\\|HEAD | /profile | profile.edit | App\Http\Controllers\ProfileController@edit | web, auth |
| PATCH | /profile | profile.update | App\Http\Controllers\ProfileController@update | web, auth |
| GET\\|HEAD | /register | register | App\Http\Controllers\Auth\RegisteredUserController@create | web, guest |
| POST | /register | - | App\Http\Controllers\Auth\RegisteredUserController@store | web, guest |
| POST | /reset-password | password.store | App\Http\Controllers\Auth\NewPasswordController@store | web, guest |
| GET\\|HEAD | /reset-password/{token} | password.reset | App\Http\Controllers\Auth\NewPasswordController@create | web, guest |
| GET\\|HEAD | /storage/{path} | storage.local | Closure | - |
| GET\\|HEAD | /student | student.dashboard | App\Http\Controllers\Student\DashboardController@index | web, auth, student |
| GET\\|HEAD | /student/courses | student.courses.index | App\Http\Controllers\Student\CoursesController@index | web, auth, student |
| GET\\|HEAD | /student/homeworks | student.homeworks.index | App\Http\Controllers\Student\HomeworksController@index | web, auth, student |
| GET\\|HEAD | /super/dashboard | super.dashboard | App\Http\Controllers\SuperAdmin\DashboardController@index | web, auth, super_admin |
| POST | /super/schools | super.schools.store | App\Http\Controllers\SuperAdmin\SchoolController@store | web, auth, super_admin |
| DELETE | /super/schools/{school} | super.schools.destroy | App\Http\Controllers\SuperAdmin\SchoolController@destroy | web, auth, super_admin |
| PUT | /super/schools/{school} | super.schools.update | App\Http\Controllers\SuperAdmin\SchoolController@update | web, auth, super_admin |
| GET\\|HEAD | /super/schools/{school}/edit | super.schools.edit | App\Http\Controllers\SuperAdmin\SchoolController@edit | web, auth, super_admin |
| GET\\|HEAD | /super/schools/create | super.schools.create | App\Http\Controllers\SuperAdmin\SchoolController@create | web, auth, super_admin |
| GET\\|HEAD | /teacher | teacher.dashboard | App\Http\Controllers\Teacher\DashboardController@index | web, auth, teacher |
| GET\\|HEAD | /teacher/assessments | teacher.assessments.index | App\Http\Controllers\Teacher\AssessmentsController@index | web, auth, teacher |
| POST | /teacher/assessments | teacher.assessments.store | App\Http\Controllers\Teacher\AssessmentsController@store | web, auth, teacher |
| GET\\|HEAD | /teacher/assessments/create | teacher.assessments.create | App\Http\Controllers\Teacher\AssessmentsController@create | web, auth, teacher |
| GET\\|HEAD | /teacher/attendance | teacher.attendance.index | App\Http\Controllers\Teacher\AttendanceController@index | web, auth, teacher |
| POST | /teacher/attendance | teacher.attendance.store | App\Http\Controllers\Teacher\AttendanceController@store | web, auth, teacher |
| GET\\|HEAD | /teacher/courses | teacher.courses.index | App\Http\Controllers\Teacher\CourseController@index | web, auth, teacher |
| POST | /teacher/courses | teacher.courses.store | App\Http\Controllers\Teacher\CourseController@store | web, auth, teacher |
| GET\\|HEAD | /teacher/courses/create | teacher.courses.create | App\Http\Controllers\Teacher\CourseController@create | web, auth, teacher |
| GET\\|HEAD | /teacher/grades | teacher.grades.index | App\Http\Controllers\Teacher\GradesController@index | web, auth, teacher |
| POST | /teacher/grades | teacher.grades.store | App\Http\Controllers\Teacher\GradesController@store | web, auth, teacher |
| GET\\|HEAD | /teacher/homeworks | teacher.homeworks.index | App\Http\Controllers\Teacher\HomeworkController@index | web, auth, teacher |
| POST | /teacher/homeworks | teacher.homeworks.store | App\Http\Controllers\Teacher\HomeworkController@store | web, auth, teacher |
| GET\\|HEAD | /teacher/homeworks/create | teacher.homeworks.create | App\Http\Controllers\Teacher\HomeworkController@create | web, auth, teacher |
| GET\\|HEAD | /teacher/messages | teacher.messages.index | App\Http\Controllers\Teacher\MessageController@index | web, auth, teacher |
| POST | /teacher/messages | teacher.messages.store | App\Http\Controllers\Teacher\MessageController@store | web, auth, teacher |
| GET\\|HEAD | /teacher/messages/{message} | teacher.messages.show | App\Http\Controllers\Teacher\MessageController@show | web, auth, teacher |
| GET\\|HEAD | /teacher/messages/create | teacher.messages.create | App\Http\Controllers\Teacher\MessageController@create | web, auth, teacher |
| GET\\|HEAD | /up | - | Closure | - |
| GET\\|HEAD | /verify-email | verification.notice | App\Http\Controllers\Auth\EmailVerificationPromptController | web, auth |
| GET\\|HEAD | /verify-email/{id}/{hash} | verification.verify | App\Http\Controllers\Auth\VerifyEmailController | web, auth, signed, throttle:6,1 |

## 5) Local run guide (Windows)

### Prerequisites
- PHP 8.2+
- Composer
- Node.js + npm
- MySQL 8+
- Optional: Redis (not mandatory with current env defaults)

### Install + setup
1. `composer install`
2. `npm install`
3. `copy .env.example .env` (if missing)
4. `php artisan key:generate`
5. Create MySQL DB (example): `schoolapp`
6. Update `.env` DB settings (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
7. `php artisan migrate --seed`
8. Recommended: `php artisan storage:link`

### Run locally
1. Backend: `php artisan serve`
2. Frontend assets: `npm run dev`
3. Open `http://127.0.0.1:8000`

### Test environment (MySQL)
- `.env.testing` is configured for MySQL (`schoolapp_test`)
- `phpunit.xml` also forces MySQL test DB
- Create DB `schoolapp_test` first
- Run tests: `php artisan test`

### Required/optional services
- Required: MySQL
- Optional but present in config:
  - Queue worker if using async jobs (`QUEUE_CONNECTION=database` in default env)
  - Redis (only if you switch cache/session/queue to redis)
  - Mail catcher/SMTP if using real mail sending

### Multi-subdomain env recommendations
- Local (XAMPP + hosts):
  - `APP_URL=http://myedu.test`
  - `APP_BASE_DOMAINS=myedu.test,myedu.school`
  - `SESSION_DOMAIN=.myedu.test`
  - `SESSION_SECURE_COOKIE=false`
  - `SESSION_SAME_SITE=lax`
- Production (Hostinger VPS, HTTPS):
  - `APP_URL=https://myedu.school`
  - `APP_BASE_DOMAINS=myedu.school`
  - `SESSION_DOMAIN=.myedu.school`
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_SAME_SITE=lax`
  - keep route/middleware unchanged, ensure web server wildcard subdomain points to app.

### Unified Navbar Branding Rule
- Navbar branding is now school-first:
  - if `currentSchool.logo_path` (or legacy `logo`) exists, display school logo in navbar.
  - otherwise fallback to MyEdu logo.
- School name remains visible in navbar across roles (admin/teacher/parent/student/director/super_admin).
- Implementation stays compatible with `SetCurrentSchool` host/subdomain resolution and storage symlink (`storage:link`).

### Notifications Page Stabilization
- Notifications rendering is unified on `resources/views/notifications/index.blade.php` for role routes:
  - `admin.notifications.index`
  - `teacher.notifications.index`
  - `student.notifications.index`
  - `parent.notifications.index` (via parent controller returning the same view)
- Open/read actions remain role-safe and ownership-protected.
- Empty-state fallback is explicit: `No notifications yet`.

### Deploy Readiness Notes
- Build/caching pipeline currently validates successfully:
  - `npm run build`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
- Tests pass (`32 passed`) when configuration cache is cleared before test execution (`php artisan config:clear`), then `php artisan test`.
- Infrastructure requirements for VPS should include common Laravel extensions and additionally `intl`, `gd`, `zip` for operational completeness.
- Repo hygiene requirement before deploy:
  - do not version compiled Blade cache files under `storage/framework/views`.

### First successful run checklist
- [ ] `php artisan migrate --seed` succeeds
- [ ] `php artisan route:list` succeeds
- [ ] Can access `/login`
- [ ] Can login and redirect by role works
- [ ] Can open one admin CRUD page (example `/admin/students`)
- [ ] `php artisan test` passes

## 6) Missing / broken / risk analysis

### Confirmed broken/incomplete
- No P0 blocker confirmed in this audit.
- Areas still requiring focused validation: timetable admin drag/drop constraints and role-specific visibility checks.

### Runtime/environment risks
- `php artisan db:show` fails locally because PHP `intl` extension is missing (developer tooling gap)
- Large legacy migration history (duplicate/patch-style migrations) increases install fragility risk despite current successful status
- Message schema variance across environments (`target_*` vs `recipient_*`) remains a compatibility risk if not handled consistently in new code.

### Quality gaps
- Tests focus mostly on auth/profile; little feature coverage for core school modules (students, finance, messaging, transport)
- `DatabaseSeeder` seeds only a generic test user; no full role/school bootstrap dataset
- `README.md` is still default Laravel README; no project-specific runbook there

### Repo hygiene risks
- Project root contains stray zero-byte files (example names like `as('admin.')`, `name('create')`)
- Several files contain mojibake/encoding-corrupted comments, reducing maintainability

### Prioritized TODO (production readiness)
1. P0: Validate timetable end-to-end (admin CRUD/move + parent/student/teacher visibility and scoping)
2. P1: Add end-to-end feature tests for role dashboards + critical CRUD + messaging/timetable access boundaries
3. P1: Improve seeders with realistic role/school/classroom baseline data
4. P1: Reduce migration complexity where safe (consolidate legacy patch migrations)
5. P2: Clean repository artifacts and normalize file encoding/comments
6. P2: Replace default README with project-specific setup/ops documentation
