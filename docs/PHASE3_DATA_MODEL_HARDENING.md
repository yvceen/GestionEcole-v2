# Phase 3 Data Model Hardening

## Migration Audit Summary

### Academic core
- `teacher_subject` -> `teacher_subjects` is a corrective chain, not a clean lineage.
  Action: keep legacy migrations, add safe corrective migrations only.
- `subjects`, `assessments`, and `grades` were created, then patched repeatedly with `school_id`, indexes, and missing columns.
  Action: keep as-is historically, continue stabilizing with additive migrations.
- `teacher_subjects` still carried a legacy global unique index on `(teacher_id, subject_id)` after the canonical school-scoped unique index existed.
  Action: remove the legacy duplicate unique index in a new corrective migration.

### School ownership drift
- `messages`, `news`, `appointments`, `courses`, `course_attachments`, and `homework_attachments` are school-owned in practice but did not all have school foreign keys.
  Action: add safe foreign keys where existing data is valid.
- `school_lives` previously lacked `school_id`.
  Action: already corrected with a nullable ownership column and safe backfill.

### Fresh install notes
- Fresh installs are currently reliable because later corrective migrations normalize the final schema.
- Existing installs may still contain historical drift, so new hardening migrations should be conditional and backfill from trusted relations when possible.

## Ownership Classification

### Global / reference tables
- `schools`
- `password_reset_tokens`
- `sessions`
- framework job/cache tables

### School-owned tables

| Table | `school_id` | Notes |
| --- | --- | --- |
| `users` | nullable | super admins remain global |
| `levels` | nullable | should be school-owned in practice |
| `classrooms` | nullable | should follow level ownership |
| `students` | nullable | expected to be school-owned |
| `subjects` | nullable | school-scoped catalog |
| `teacher_subjects` | nullable | canonical teacher-subject pivot |
| `assessments` | required | academic core |
| `grades` | required | academic core |
| `courses` | required | teacher/admin content |
| `course_attachments` | required | should match course ownership |
| `homeworks` | required | teacher/admin content |
| `homework_attachments` | required | should match homework ownership |
| `messages` | required | messaging is school-scoped |
| `news` | nullable | school-owned, classroom-scoped when needed |
| `appointments` | nullable | school-owned, parent-linked |
| `vehicles` | required | transport module |
| `routes` | required | transport module |
| `transport_assignments` | required | transport module |
| `school_lives` | nullable | newly hardened |
| `timetables` | nullable | school-facing schedules |
| `timetable_settings` | nullable | school-facing settings |

### Relationship tables needing extra validation
- `classroom_teacher`
- `teacher_subjects`
- `assessments`
- `grades`
- `news` when `classroom_id` is present
- `appointments` when `parent_user_id` is present
- `transport_assignments`
- `course_attachments`
- `homework_attachments`

## Recommended Actions

### Keep as-is
- Historical migration chain itself.
- Legacy patch migrations that are already part of production history.

### Add safe corrective migrations
- school ownership foreign keys on content tables already carrying `school_id`
- backfills for nullable ownership columns from trusted parent relations
- removal of obvious duplicate/legacy unique indexes once canonical indexes exist

### Defer for later consolidation
- rewriting the teacher-subject migration story
- making nullable ownership columns non-null across all installs
- composite database constraints enforcing same-school relationships everywhere
- broader cleanup of legacy message recipient schema fallbacks
