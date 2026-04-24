# Progress

Last updated: 2026-02-19

## Last Modification (2026-02-19)

- [x] Documentation sync after latest timetable and messaging changes
  - Added explicit latest-change snapshot in `docs/PROJECT_OVERVIEW.md`
  - Refreshed runtime route metrics from `php artisan route:list --json`
  - Confirmed timetable endpoints now active for admin/teacher/parent/student flows

## Multi-school subdomain readiness + unified navbar hardening (2026-02-19)

- [x] Subdomain school resolver hardened in middleware:
  - `SetCurrentSchool` now resolves school from host/subdomain (`{slug}.myedu.test`, `{slug}.myedu.school`) using `APP_BASE_DOMAINS`.
  - Host-school mismatch protection added for non-super users (403 if user school != host school).
  - Backward-safe fallback kept: if no subdomain school, uses authenticated user `school_id`.
  - Strict school requirement applies only on school role spaces (`/admin`, `/teacher`, `/parent`, `/student`, `/director`) to avoid breaking generic auth/profile routes.
  - Bound/shared globally: `currentSchool`, `current_school`, `current_school_id`.
- [x] Schools schema safety migration added:
  - `database/migrations/2026_02_19_233000_ensure_schools_slug_and_logo_columns.php`
  - Ensures columns exist: `slug`, `logo_path`
  - Backfills unique slugs from existing names
  - Enforces unique index `schools_slug_unique` safely
- [x] Session/subdomain prep:
  - `.env.example` updated with `APP_BASE_DOMAINS` and recommended `SESSION_DOMAIN` local/prod values.
- [x] Unified navbar branding polish:
  - `app-navbar` always shows MyEdu logo + MyEdu text.
  - Displays school logo/name when available.
  - Contrast improved for text/search placeholders.
- [x] Click issue root cause + fix:
  - Cause: mobile sidebar was manipulated directly from navbar (inline style toggling) + layering fragility.
  - Fix: mobile sidebar state centralized in `app-shell`; navbar dispatches Alpine events only (`toggle-mobile-sidebar`, `close-mobile-sidebar`), with overlay/ESC close.

### Files changed
- `app/Http/Middleware/SetCurrentSchool.php`
- `resources/views/components/app-navbar.blade.php`
- `resources/views/components/app-shell.blade.php`
- `database/migrations/2026_02_19_233000_ensure_schools_slug_and_logo_columns.php` (new)
- `.env.example`
- `docs/PROGRESS.md`
- `docs/PROJECT_OVERVIEW.md`

### Commands executed
- `php artisan migrate --force`
- `php artisan storage:link` (already existed)
- `php artisan optimize:clear`
- `php artisan view:clear`
- `php artisan route:list`
- `php artisan test` (`32 passed`)

### Manual verification checklist (local subdomains)
- [ ] Add hosts entries:
  - `127.0.0.1 achbalryad.myedu.test`
  - `127.0.0.1 atlas.myedu.test`
- [ ] Ensure matching `schools.slug` values exist: `achbalryad`, `atlas`.
- [ ] Login as each role on each subdomain and verify data is school-isolated.
- [ ] Verify navbar on all role portals shows:
  - MyEdu logo + MyEdu text
  - school logo/name when available
- [ ] Mobile: hamburger opens sidebar, overlay click closes, ESC closes.

## Navbar mobile hamburger fix (2026-02-19)

- [x] Root cause:
  - hamburger toggle et sidebar mobile etaient pilotes depuis `app-navbar` en manipulant directement le style du `<aside>`, ce qui etait fragile et non fiable selon le contexte responsive/z-index.
- [x] Fix applique:
  - etat mobile du sidebar centralise dans `resources/views/components/app-shell.blade.php` (source layout du sidebar)
  - `app-navbar` envoie uniquement des events Alpine (`toggle-mobile-sidebar`, `close-mobile-sidebar`)
  - overlay + fermeture au clic overlay + fermeture ESC + `aria-expanded/aria-controls` assures
  - orbs decoratifs en `pointer-events-none` pour eviter tout blocage clic
- [x] Fichiers modifies:
  - `resources/views/components/app-shell.blade.php`
  - `resources/views/components/app-navbar.blade.php`
- [x] Test manuel:
  - mobile: clic hamburger => sidebar ouvre + overlay visible
  - clic overlay => ferme
  - touche ESC => ferme
  - desktop: sidebar reste stable et visible

## Unified Navbar System (Single Component) + Dynamic School Branding (2026-02-19)

- [x] Navbar unifiee pour tous les roles via un composant unique:
  - `resources/views/components/app-navbar.blade.php` (new single source of truth)
  - integree dans `resources/views/components/app-shell.blade.php`
- [x] Roles couverts avec meme design/comportement:
  - admin, director, teacher, parent, student, super_admin
- [x] Branding dynamique ecole:
  - logo ecole (`logo` ou `logo_path`) + nom ecole (`currentSchool/current_school`)
  - fallback `x-school-logo` si pas de logo
- [x] Comportements communs centralises:
  - recherche
  - cloche notifications + badge non lus + dropdown
  - menu profil + logout
  - quick links role-aware (style identique)
- [x] Compatibilite middleware:
  - `SetCurrentSchool` expose maintenant aussi alias `currentSchool` (en plus de `current_school`)

## Devoirs: workflow admin approve + notifications multi-roles (2026-02-19)

- [x] P0 debug 500 teacher devoirs (POST `/teacher/homeworks`) corrige.
  - Cause racine: insertion `homework_attachments` sans `school_id` alors que la colonne est non nullable.
  - Fix: `Teacher\\HomeworkController@store` renseigne `school_id` de facon schema-aware avant `HomeworkAttachment::create`.
- [x] Devoirs normalises et workflow admin complete:
  - Teacher create => `status = pending` (si colonne `status` existe)
  - Admin module devoirs enrichi:
    - `GET /admin/homeworks` (recherche `title|classroom|teacher`, filtre status, stats cards, pending-first)
    - `GET /admin/homeworks/{homework}` (detail)
    - `POST /admin/homeworks/{homework}/approve`
    - `POST /admin/homeworks/{homework}/reject`
  - Parent + Student voient uniquement les devoirs `approved|confirmed` si colonne status existe.
  - Navigation admin rendue visible:
    - menu sidebar `Devoirs` ajoute en route-safe (`Route::has('admin.homeworks.index')`)
    - carte dashboard admin `Devoirs en attente` + bouton vers la liste
- [x] Notifications multi-roles strict targeting:
  - Table `notifications` etendue avec `recipient_user_id`, `recipient_role` (backward-safe avec `user_id` legacy).
  - Service unifie `NotificationService::notifyUsers(...)` (dedupe, ignore empty, bulk insert, no broadcast fallback).
  - Triggers connectes:
    - Teacher submit homework -> notif admins de la meme ecole (`Devoir en attente`)
    - Rendez-vous approve/reject -> parent concerne uniquement
    - Messages admin store + approve -> destinataires reels uniquement (user target ou parents de classe)
    - Devoirs approve -> parents + eleves + enseignant de la classe/devoir; reject -> enseignant uniquement
    - Cours approve/reject idem logique devoirs
    - Actualites scope classroom/school -> parents + eleves dans le scope seulement
- [x] Cloche notifications et pages role-based:
  - Bell + unread badge dans `x-app-shell` pour `admin|teacher|student|parent`
  - Pages:
    - `/admin/notifications`
    - `/teacher/notifications`
    - `/student/notifications`
    - `/parent/notifications` (existant conserve)

### Fichiers modifies/crees (scope de cette etape)
- `app/Http/Controllers/Teacher/HomeworkController.php`
- `app/Http/Controllers/Admin/HomeworkController.php`
- `app/Http/Controllers/Admin/CourseController.php`
- `app/Http/Controllers/Admin/MessageController.php`
- `app/Http/Controllers/Admin/NewsController.php`
- `app/Http/Controllers/Admin/AppointmentController.php`
- `app/Http/Controllers/Student/HomeworksController.php`
- `app/Http/Controllers/Parent/NotificationController.php`
- `app/Http/Controllers/NotificationCenterController.php` (new)
- `app/Models/Homework.php`
- `app/Models/Course.php`
- `app/Models/User.php`
- `app/Models/AppNotification.php` (new)
- `app/Models/ParentNotification.php`
- `app/Services/NotificationService.php`
- `resources/views/admin/homeworks/index.blade.php`
- `resources/views/admin/homeworks/show.blade.php` (new)
- `resources/views/admin/courses/index.blade.php`
- `resources/views/components/app-shell.blade.php`
- `resources/views/components/admin-layout.blade.php`
- `resources/views/components/teacher-layout.blade.php`
- `resources/views/components/student-layout.blade.php`
- `resources/views/notifications/index.blade.php` (new)
- `routes/web.php`
- `database/migrations/2026_02_19_220000_add_review_columns_to_homeworks_table.php` (new)
- `database/migrations/2026_02_19_221000_add_recipient_columns_to_notifications_table.php` (new)
- `database/migrations/2026_02_19_222000_add_rejected_columns_to_courses_table.php` (new)
- `tests/Feature/HomeworkWorkflowNotificationTest.php` (new)

### Routes ajoutees
- `GET /admin/homeworks/{homework}` -> `admin.homeworks.show`
- `POST /admin/homeworks/{homework}/reject` -> `admin.homeworks.reject`
- `POST /admin/courses/{course}/reject` -> `admin.courses.reject`
- `GET /admin/notifications` -> `admin.notifications.index`
- `GET /admin/notifications/{notification}/open` -> `admin.notifications.open`
- `POST /admin/notifications/read-all` -> `admin.notifications.read_all`
- `GET /teacher/notifications` -> `teacher.notifications.index`
- `GET /teacher/notifications/{notification}/open` -> `teacher.notifications.open`
- `POST /teacher/notifications/read-all` -> `teacher.notifications.read_all`
- `GET /student/notifications` -> `student.notifications.index`
- `GET /student/notifications/{notification}/open` -> `student.notifications.open`
- `POST /student/notifications/read-all` -> `student.notifications.read_all`
- `POST /parent/notifications/read-all` -> `parent.notifications.read_all`

### Migrations ajoutees
- `database/migrations/2026_02_19_220000_add_review_columns_to_homeworks_table.php`
- `database/migrations/2026_02_19_221000_add_recipient_columns_to_notifications_table.php`
- `database/migrations/2026_02_19_222000_add_rejected_columns_to_courses_table.php`

### Checklist manuel
- [ ] Enseignant: creer un devoir avec piece jointe -> pas de 500
- [ ] Admin: ouvrir `/admin/homeworks`, verifier pending-first + filtres + badges
- [ ] Admin: Approve un devoir pending -> status `approved`, notif ciblee creee
- [ ] Admin: Reject un devoir pending -> status `rejected`, notif enseignant creee
- [ ] Parent/Student: liste devoirs affiche uniquement les devoirs approved
- [ ] Admin/Teacher/Student/Parent: bell affiche badge non lus + dropdown + ouverture marque lu

## Parent Notification System (Strict Targeting) (2026-02-19)

- [x] Core notifications table added (one row per parent recipient, no multi-parent row).
  - `database/migrations/2026_02_19_140000_create_notifications_table.php`
  - Columns: `id`, `user_id`, `type`, `title`, `body`, `data`, `read_at`, timestamps
- [x] Notification service added with bulk insert + safety guards.
  - `app/Services/NotificationService.php`
  - `notifyParents(array $parentIds, string $type, string $title, string $body, array $data = [])`
  - Guard: if recipients count = 0 => no insert, no broadcast fallback
- [x] Strict targeting logic connected:
  - Rendez-vous admin approve/reject -> notify only `appointment.parent_user_id`
  - Admin message send:
    - direct parent target -> notify only selected parent IDs
    - classroom target -> notify only parents linked via `students.classroom_id`
  - Homework -> notify only classroom parents on admin approve
  - Course -> notify only classroom parents on admin approve
  - News -> scope-aware:
    - `classroom` (default) => classroom parents only
    - `school` => parents of same school only
- [x] Parent notification bell UI added (targeted only):
  - unread badge counter
  - dropdown latest notifications
  - open notification marks it as read
- [x] Parent notifications list page added.

### Routes added
- `POST /admin/homeworks/{homework}/approve` -> `admin.homeworks.approve`
- `POST /admin/courses/{course}/approve` -> `admin.courses.approve`
- `GET /parent/notifications` -> `parent.notifications.index`
- `GET /parent/notifications/{notification}/open` -> `parent.notifications.open`
- `POST /parent/notifications/{notification}/read` -> `parent.notifications.read`

### Migrations added
- `database/migrations/2026_02_19_140000_create_notifications_table.php`
- `database/migrations/2026_02_19_141000_add_scope_columns_to_news_table.php`

### Files changed (notification step)
- `app/Services/NotificationService.php` (new)
- `app/Models/ParentNotification.php` (new)
- `app/Models/User.php`
- `app/Models/Course.php`
- `app/Models/Homework.php`
- `app/Models/News.php`
- `app/Http/Controllers/Admin/AppointmentController.php`
- `app/Http/Controllers/Admin/MessageController.php`
- `app/Http/Controllers/Admin/HomeworkController.php`
- `app/Http/Controllers/Admin/CourseController.php`
- `app/Http/Controllers/Admin/NewsController.php`
- `app/Http/Controllers/Parent/NotificationController.php` (new)
- `app/Http/Controllers/Parent/HomeworkController.php`
- `app/Http/Controllers/Parent/CoursesController.php`
- `resources/views/components/app-shell.blade.php`
- `resources/views/components/parent-layout.blade.php`
- `resources/views/admin/homeworks/index.blade.php`
- `resources/views/admin/courses/index.blade.php`
- `resources/views/admin/news/create.blade.php`
- `resources/views/parent/notifications/index.blade.php` (new)
- `routes/web.php`
- `docs/PROGRESS.md`
- `docs/PROJECT_OVERVIEW.md`

### Manual verification checklist
- [ ] Send homework to Class A, approve as admin -> only parents of Class A get notification
- [ ] Send message to one parent -> only that parent receives notification
- [ ] Approve/reject rendez-vous -> only requesting parent receives notification
- [ ] Create classroom news -> only parents of that classroom receive notification
- [ ] Login as unrelated parent -> zero unrelated notification visible

## Rendez-vous admin workflow upgraded (2026-02-19)

- [x] Status display fixed (no empty badge) with legacy-safe normalization:
  - `draft|pending|empty => pending`
  - `confirmed|approved => approved`
  - `archived|cancelled|rejected => rejected`
- [x] Admin index UI upgraded (EdTech style preserved):
  - premium header + quick stats cards
  - search + status filter (`all|pending|approved|rejected`)
  - pending-first sorting, then newest
  - table actions: `View`, `Approve`, `Reject`, `Copy phone`, optional `Parent profile` link (route-safe)
- [x] Admin actions added:
  - `POST /admin/appointments/{appointment}/approve` (`admin.appointments.approve`)
  - `POST /admin/appointments/{appointment}/reject` (`admin.appointments.reject`)
  - `GET /admin/appointments/{appointment}` (`admin.appointments.show`)
- [x] Schema additions via migration only (no old migration edit):
  - `approved_at`, `approved_by`, `rejected_at`, `rejected_by` on `appointments`
- [x] Schema-aware handling kept in controller for legacy installs (`scheduled_at` vs `date`, status legacy values)

### Files changed (Rendez-vous scope)
- `routes/web.php`
- `app/Http/Controllers/Admin/AppointmentController.php`
- `app/Models/Appointment.php`
- `resources/views/admin/appointments/index.blade.php`
- `resources/views/admin/appointments/show.blade.php` (new)
- `database/migrations/2026_02_19_130000_add_review_columns_to_appointments_table.php` (new)
- `docs/PROGRESS.md`
- `docs/PROJECT_OVERVIEW.md`

### Manual test (Rendez-vous)
- [ ] Parent submit request from `/parent/appointments/create`
- [ ] Admin open `/admin/appointments` and verify new row appears with `PENDING` badge
- [ ] Click `Approve`, verify badge switches to `APPROVED`, success flash, and DB `approved_at/approved_by` set
- [ ] Click `Reject` on another pending request, verify `REJECTED`, success flash, and DB `rejected_at/rejected_by` set
- [ ] Verify search by parent name/phone/title works
- [ ] Verify status filter tabs/dropdown (`All/Pending/Approved/Rejected`) work
- [ ] Verify `View` page `/admin/appointments/{id}` renders details

## Discovery Deliverables

- [x] Full owner-level audit completed
- [x] Stack/framework/version verification completed
- [x] Route inventory exported from runtime (`php artisan route:list --json`)
- [x] Database migration/state audit completed (`php artisan migrate:status`)
- [x] Test suite status checked (`29 passed` on MySQL test DB)
- [x] `docs/PROJECT_OVERVIEW.md` refreshed with complete findings

## Step-by-Step Fixes

- [x] STEP 1 complete: Finance placeholders replaced safely in `FinanceController`
  - `unpaid()` now returns guarded real unpaid data when finance tables exist, otherwise safe empty payload
  - `printStatement()` no longer aborts; returns safe statement view with fallback message (`Not implemented yet`)
  - Verification:
    - `php artisan test` passed (`25 passed`)
    - `php artisan route:list` passed (no routes removed)
    - No migrations altered
- [x] STEP 2 complete: Removed leading spaces in `DB_*` lines in `.env.example` (no runtime impact)
- [x] STEP 3 complete: Documented intl requirement + alternatives for `db:show`
- [x] STEP 4 complete: Added minimal smoke tests for student/parent/admin core portal flows
  - Added `tests/Feature/Student/StudentPortalTest.php`
  - Added `tests/Feature/Parent/ParentPortalTest.php`
  - Added `tests/Feature/Admin/AdminStudentCreateTest.php`
  - Run locally: `C:\xampp\php\php.exe artisan test`
  - Result: `29 passed`
- [x] UI update complete: Switched authenticated UI to EdTech blue+teal theme + background (UI only).
- [x] Module Emploi du temps ajoute (admin + eleve + parent + enseignant lecture)
  - Table `timetables` creee avec `school_id`, `classroom_id`, `day`, `start_time`, `end_time`, `subject`, `teacher_id`, `room`
  - Validation anti-chevauchement active cote admin (meme classe + meme jour + plage horaire)
  - Vues ajoutees:
    - `admin/timetable/index|create|edit`
    - `student/timetable/index`
    - `parent/timetable/index`
    - `teacher/timetable/index`
  - Navigation ajoutee (Route::has) pour admin/eleve/enseignant + lien parent depuis "Mes enfants"
  - Seed minimal ajoute via `TimetableSeeder`

### Checklist manuel Emploi du temps
- [ ] Admin:
  - [ ] Ouvrir `/admin/timetable`
  - [ ] Choisir une classe, verifier la grille semaine
  - [ ] Ajouter un creneau valide
  - [ ] Tenter un creneau qui chevauche un existant -> erreur de validation attendue
  - [ ] Modifier et supprimer un creneau
- [ ] Eleve:
  - [ ] Se connecter en eleve
  - [ ] Ouvrir `/student/timetable`
  - [ ] Verifier que seule la classe de l eleve est visible
- [ ] Parent:
  - [ ] Se connecter en parent
  - [ ] Ouvrir `/parent/children`
  - [ ] Cliquer "Emploi du temps" sur un enfant
  - [ ] Verifier que la grille correspond a la classe de cet enfant
  - [ ] Tester un enfant non lie (URL directe) -> 404 attendu
- [ ] Enseignant (lecture):
  - [ ] Ouvrir `/teacher/timetable`
  - [ ] Verifier affichage des classes affectees

- [x] Upgrade UX Emploi du temps (inspiration type planning hebdomadaire)
  - Grille verticale 08:00-18:00 avec colonnes Lundi-Samedi
  - Cartes de creneaux positionnees (top/height) selon l heure
  - Couleurs stables par matiere
  - Pause dejeuner configurable et affichee
  - Nouvelle page admin de parametres:
    - `/admin/timetable/settings`
    - debut/fin journee, duree de seance, pause dejeuner

### Checklist manuel Upgrade Emploi du temps
- [ ] 1) Changer la duree de seance a 45 minutes dans `/admin/timetable/settings`, enregistrer, revenir sur `/admin/timetable` et verifier que la timeline est recalculee
- [ ] 2) Ajouter un creneau 09:00-09:45, verifier l alignement visuel de la carte dans la grille
- [ ] 3) Cliquer "Imprimer" sur les vues admin/eleve/parent/enseignant et verifier l impression
- [ ] 4) Verifier que l eleve voit uniquement sa classe sur `/student/timetable`
- [ ] 5) Verifier que le parent voit le planning du bon enfant via `/parent/children/{student}/timetable`

- [x] Upgrade admin planning: drag & drop + redimensionnement
  - Ajout endpoint admin `PUT /admin/timetable/{timetable}/move` (`admin.timetable.move`)
  - Cartes admin deplacement vertical + resize bas avec snap automatique sur `slot_minutes`
  - Garde-fous UI: blocage hors plage horaire + blocage chevauchement
  - Verification backend conservee: validation horaire + anti-chevauchement + scope classe/jour/ecole

- [x] Fix 500 on admin message send (`POST /admin/messages`)
  - Root cause:
    - Legacy schema mismatch in `messages` table.
    - Controller was always writing `target_*` + `approval_required` columns, but current DB uses `recipient_type` / `recipient_id` and does not have `approval_required`.
    - Logged error: `SQLSTATE[42S22]: Unknown column 'approval_required' in 'field list'`.
  - Changes:
    - `app/Http/Controllers/Admin/MessageController.php` (`store` only)
    - Added safe column detection via `Schema::getColumnListing('messages')`.
    - Backward-compatible write path:
      - uses `target_*` when available
      - falls back to `recipient_type` / `recipient_id` when legacy schema is used
      - inserts one row per user recipient when schema does not support multi-user JSON target list
    - Wrapped send flow in try/catch with `Log::error(...)` and safe `back()->withErrors()->withInput()` on failure.
  - Manual test:
    1. Ouvrir `/admin/messages/create`
    2. Saisir `subject` + `body`, selectionner une classe ou des destinataires
    3. Cliquer `Envoyer`
    4. Verifier redirection vers `admin.messages.show` + flash success
    5. Verifier absence de 500 dans `storage/logs/laravel.log`

- [x] Fix 500 on parent messages index (`GET /parent/messages`)
  - Root cause:
    - `Parent\MessageController@index` query referenced `target_type/target_id` directly.
    - On this install, `messages` uses legacy `recipient_type/recipient_id`, causing SQL 42S22.
    - Logged error: `Unknown column 'target_type' in 'where clause'` at `app/Http/Controllers/Parent/MessageController.php:69`.
  - Changes:
    - `app/Http/Controllers/Parent/MessageController.php`
      - Added schema-aware target column detection via `Schema::getColumnListing('messages')`.
      - Uses `target_*` when present, else `recipient_*`.
      - Applies `school_id` / `status` filters only if columns exist.
      - Keeps parent classroom lookup on `students.parent_user_id`.
      - Wrapped `index()` in `try/catch`, logs exception and returns `back()->withErrors('Unable to load messages.')`.
    - `app/Models/Message.php`
      - Hardened `scopeAddressedToUser()` with same schema-aware column detection.
  - Manual test:
    1. Se connecter en parent.
    2. Ouvrir `/parent/messages`.
    3. Verifier chargement sans 500.
    4. Verifier dans `storage/logs/laravel.log` qu aucune nouvelle erreur SQL `target_type` n apparait.

- [x] Fix 500 on teacher messages (`GET /teacher/messages` + send compatibility)
  - Root cause:
    - `Teacher\\MessageController` used `target_type/target_id` directly in inbox query.
    - On legacy schema (`recipient_type/recipient_id`), this triggered SQL 42S22 unknown column errors.
  - Changes:
    - `app/Http/Controllers/Teacher/MessageController.php`
      - Added schema-aware routing between `target_*` and `recipient_*`.
      - Hardened `index()` with safe query building + `try/catch` + error logging.
      - Updated `store()` to insert backward-compatible payloads using existing message columns.
      - Updated `show()` recipient checks to support both schema variants.
  - Manual test:
    1. Se connecter en enseignant.
    2. Ouvrir `/teacher/messages` et verifier l affichage sans 500.
    3. Envoyer un message a des parents ou a une classe.
    4. Verifier redirection avec flash succes et absence d erreur SQL dans les logs.

## Full UI Harmonization (Global)

- [x] PHASE 0 - Inventory completed
  - Layout chain verified: `x-admin-layout`, `x-teacher-layout`, `x-parent-layout`, `x-director-layout`, `x-student-layout`, `x-super-layout`, `layouts/app`, all routed through `x-app-shell` for authenticated UI.
  - Legacy/heterogeneous modules identified in admin/teacher/parent/director/super CRUD views.
  - Missing Blade view scan completed from controllers (`return view(...)`): no missing view detected.
- [x] PHASE 1 - Unified UI component system completed (`resources/views/components/ui`)
  - Added: `page-header`, `card`, `table`, `button`, `input`, `select`, `textarea`, `badge`, `alert`, `modal`, `pagination`.
- [x] PHASE 2 - Role layout harmonization completed
  - Updated admin/teacher/parent/student/director/super authenticated layouts to shared EdTech shell style and consistent flash handling.
  - Added global `ui-scope` styling layer in `resources/css/app.css` for legacy Blade pages still using old markup.
- [x] PHASE 3 - CRUD UI harmonization pass completed (safe/global)
  - Existing forms/tables/actions preserved (same routes/methods/csrf), but visual system unified through shared components + scoped global styles.
  - Students module fully componentized (`students/index/create/edit/fees` + reusable `components/students/*`).
- [x] PHASE 4 - Missing views safety check
  - No additional Blade file creation required for missing views.
- [x] PHASE 5 - Navigation polish
  - Role navigation remains route-safe (`Route::has(...)` on optional links), active state preserved in shared shell.

### Verification (after harmonization)

- [x] `C:\\xampp\\php\\php.exe artisan view:clear`
- [x] `C:\\xampp\\php\\php.exe artisan optimize:clear`
- [x] `C:\\xampp\\php\\php.exe artisan test` (`29 passed`)
- [x] `C:\\xampp\\php\\php.exe artisan route:list` (`204 routes`)

## Current Known Issues (Prioritized)

### P0 (Fix first)
- [ ] Validate end-to-end admin student create/edit paths for fee-plan persistence on production-like data volume

### P1 (Stability and quality)
- [ ] Add feature tests for core modules:
  - [ ] role redirects (admin/director/teacher/parent/student/super_admin)
  - [ ] admin student/user/subject CRUD
  - [ ] parent/student classroom content visibility
  - [ ] messaging access boundaries by role + classroom
- [ ] Expand seeders to provide realistic baseline data (school, roles, classrooms, students, parents, teachers)
- [ ] Validate fresh bootstrap from clean clone on Windows (`composer install`, `npm install`, `migrate --seed`, `serve`, `npm run dev`)

### P2 (Maintainability)
- [ ] Reduce legacy migration complexity (many patch/duplicate-style migrations)
- [ ] Clean repository artifacts (stray zero-byte files in project root)
- [ ] Normalize encoding/comments in files with mojibake text
- [ ] Replace default Laravel README with project-specific setup/ops runbook

## Verification Notes

- [x] Runtime route count: `212`
- [x] Route protection count: `194` routes include `auth` middleware
- [x] Prefix route counts:
  - [x] admin: `115`
  - [x] director: `21`
  - [x] teacher: `19`
  - [x] parent: `18`
  - [x] student: `4`
  - [x] super: `6`
- [x] `routes/api.php` is absent (no dedicated API route file)
- [x] `php artisan db:show` hits local environment issue (`intl` extension missing)

## Next Steps (Execution Order)

1. Implement/fix Finance statement + unpaid features.
2. Correct `.env.example` DB keys and re-test clean setup flow.
3. Add high-value feature tests for school-domain flows.
4. Strengthen seeders and documentation for onboarding.
5. Perform migration/refactor cleanup pass with backward-safety checks.

## Notifications page rendering fix (2026-02-19)

- Root cause:
  - `resources/views/notifications/index.blade.php` was not aligned with the unified shell pattern and could render without a clear content structure in the main slot.
  - Result: page appeared as background-only (navbar/sidebar visible, content unclear/empty).
- Fix applied:
  - Reworked notifications page to use unified layout structure:
    - `<x-app-shell title="Notifications">`
    - `<x-page-header ... />`
    - content card container with notification list.
  - Added safe empty state message: `No notifications yet`.
  - Added a small compatibility component `resources/views/components/page-header.blade.php` that proxies to `x-ui.page-header`.
  - Ensured unified sidebar can include notifications links for role defaults in `resources/views/components/app-shell.blade.php` (route-safe through existing `Route::has` checks).
- Files changed:
  - `resources/views/notifications/index.blade.php`
  - `resources/views/components/page-header.blade.php` (new)
  - `resources/views/components/app-shell.blade.php`
- Verification commands:
  - `C:\\xampp\\php\\php.exe artisan optimize:clear`
  - `C:\\xampp\\php\\php.exe artisan view:clear`
- Manual checks:
  1. Open `/admin/notifications` from sidebar.
  2. Confirm page header and list/empty state are visible.
  3. Confirm navbar + sidebar remain intact.
  4. Confirm mobile sidebar/hamburger behavior is unchanged.

## Notifications rendering + school logo navbar fix (2026-02-19)

- Root cause (notifications blank/striped):
  - Parent notifications were still using a separate view (`parent.notifications.index`) while other roles used `notifications.index`, causing inconsistent rendering behavior and leaving the unified shell path partially bypassed.
  - In addition, there was no explicit runtime trace in controller to confirm the rendered view in production-like mode.
- Fix:
  - Unified notifications rendering to a single Blade: `resources/views/notifications/index.blade.php` for admin/teacher/student/parent.
  - Added controller debug logging in both notification controllers to trace resolved view + count safely.
  - Kept `{{ $slot }}` layout flow intact (`resources/views/components/app-shell.blade.php`) and confirmed decorative layers are `pointer-events-none`.
  - Preserved open/read behavior (open marks read, read-all stays route-safe).
- Navbar branding update:
  - Updated unified navbar to show current school logo first when available (`logo_path` or `logo`), with MyEdu logo as fallback.
  - School name is displayed in navbar branding, keeping multi-school context visible across roles.
- Files changed:
  - `app/Http/Controllers/NotificationCenterController.php`
  - `app/Http/Controllers/Parent/NotificationController.php`
  - `resources/views/components/app-navbar.blade.php`
- Commands run:
  - `C:\\xampp\\php\\php.exe artisan view:clear`
  - `C:\\xampp\\php\\php.exe artisan optimize:clear`
  - `C:\\xampp\\php\\php.exe artisan route:list --name=notifications`
- Manual checks:
  1. Admin: `/admin/notifications` renders list or `No notifications yet`.
  2. Parent: `/parent/notifications` renders the same unified notifications UI.
  3. Open a notification: marked read + redirected to target.
  4. Navbar displays school logo when configured, otherwise MyEdu logo fallback.

## Pre-deploy full audit (2026-02-19)

- Scope: Laravel 12 + Vite (Windows/XAMPP local), production readiness for VPS deploy.

- Checks passed:
  - `composer install --no-dev --dry-run --no-interaction --prefer-dist` succeeded.
  - `composer check-platform-reqs` succeeded for current installed dependencies.
  - `npm install` succeeded.
  - `npm run build` succeeded (after elevating local shell permission; build output generated in `public/build`).
  - `php artisan route:list` succeeded (225 routes, no broken controller/action resolution).
  - `php artisan view:cache` succeeded (Blade compilation OK, no missing component/include in current render paths).
  - `php artisan migrate:status` shows all migrations applied.
  - `php artisan route:cache`, `php artisan config:cache`, `php artisan view:cache` all succeeded.
  - `php artisan storage:link` confirms symlink exists.
  - `php artisan test` passes (`32 passed`) when config cache is cleared first.

- Warnings / risks:
  - Repository hygiene issue: many compiled views are tracked under `storage/framework/views/*` (should not be versioned).
  - Working tree is not clean (many modified + untracked files), and branch is ahead of origin.
  - Local PHP is missing `intl`, `gd`, `zip` extensions (`php -m`); this can affect tools/features on VPS if similarly missing.
  - `config/app.php` uses `env('APP-NAME', 'My-Edu')` (hyphen) instead of standard `APP_NAME`.
  - Running tests immediately after `config:cache` produced CSRF 419 failures; `config:clear` before tests resolves it.
  - MySQL CLI tool in this local environment fails auth plugin load (`caching_sha2_password.dll`), though Laravel DB access works.

- Blocking before deploy:
  - Remove compiled Blade artifacts from git tracking and ignore them (`storage/framework/views/*`) to avoid noisy/unsafe deploy diffs.
  - Ensure VPS PHP extensions include at minimum: `mbstring`, `openssl`, `pdo_mysql`, `xml`, plus recommended `intl`, `gd`, `zip`.

- Commands executed during audit:
  - `git status --short --branch`
  - `composer install --no-dev --dry-run --no-interaction --prefer-dist`
  - `composer check-platform-reqs`
  - `npm install`
  - `npm run build`
  - `php artisan route:list`
  - `php artisan migrate:status`
  - `php artisan optimize:clear`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `php artisan test`

## Pre-deploy repository cleanup (2026-02-19)

- Cleanup scope: Git hygiene only (no logic/routes/UI changes).
- Added runtime artifact ignore rules in `.gitignore`:
  - `storage/framework/views/*`
  - `storage/framework/cache/*`
  - `storage/framework/sessions/*`
  - `storage/logs/*`
  - `bootstrap/cache/*.php`
  - `public/build/*`
- Removed already tracked runtime/generated files from Git index (kept locally): compiled Blade/cache/log artifacts.
- Outcome: runtime artifacts are no longer versioned, repository is safer for VPS deployment workflows.
