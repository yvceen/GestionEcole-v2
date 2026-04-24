# UI Refactor Plan (living document)
Location: `C:\xampp\htdocs\GestionEcole`

---
## UI Owners Map (single sources of truth)
- Shell layout: `resources/views/components/app-shell.blade.php`
  - Owns background hook (`app-shell-body`), flex row (sidebar + main), sidebar widths/heights, mobile drawer.
- Navbar: `resources/views/components/app-navbar.blade.php`
  - Sticky glass bar, three zones, notifications/profile logic.
- Sidebar: inside `app-shell.blade.php`
  - Desktop and mobile drawers share the same glass style and item structure.
- Role layouts: `resources/views/components/*-layout.blade.php`
  - Thin wrappers that feed `:links` into `x-app-shell`.
- Design tokens / global UI CSS: `resources/css/app.css`
  - `--navbar-h`, `--sidebar-w`, `.glass-nav`, `.ui-card`, `.ui-glass`, `.ui-title`, `.ui-muted-text`, `.scrollbar-hidden`, `.app-shell-body` background.
- Page headers: new `resources/views/components/page-header.blade.php`
  - Standard title/subtitle/actions layout.
- Key pages normalized (Stage 5):
  - `admin/dashboard.blade.php`
  - `admin/students/index.blade.php`
  - `admin/users/index.blade.php`
  - `super/schools/index.blade.php`
  - `super/schools/create.blade.php`
  - `super/schools/edit.blade.php`

## What was removed / de-duplicated
- Navbar stickiness is only in Blade (`app-navbar`)—conflicting CSS stickies removed earlier.
- Background ownership consolidated into `.app-shell-body`; no extra page-level gradients added.
- Page headers now use `x-page-header` instead of ad-hoc flex blocks in the pages above.
- Sidebar glass styling centralized inside `app-shell`; no per-page sidebar hacks.

## Design tokens (current)
- `--navbar-h: 64px`
- `--sidebar-w: 280px`
- Utilities: `.ui-container`, `.ui-card`, `.ui-glass`, `.ui-title`, `.ui-muted-text`, `.scrollbar-hidden`
- Components: `.glass-nav`; `.app-shell-body` owns the global subtle background.

## Testing checklist (post-change)
1) Layout alignment
   - `/admin`, `/admin/students`, `/admin/users`
   - Sidebar flush under navbar; no top gap; main `min-w-0` and no horizontal scroll.
2) Mobile
   - Sidebar drawer opens/closes; body scroll locks; nav items scroll if long.
3) Super role
   - `/super/schools`, `/super/schools/create`, `/super/schools/edit` headers consistent; tables scroll on small widths.
4) Guest
   - `/login` unaffected (guest layout intact).
5) Accessibility quick pass
   - Focus rings visible on navbar buttons and sidebar links.

## Rollback steps (manual, no git repo available)
1) Backup current files before reverting:
   - `resources/views/components/app-shell.blade.php`
   - `resources/views/components/app-navbar.blade.php`
   - `resources/views/components/page-header.blade.php`
   - `resources/views/admin/dashboard.blade.php`
   - `resources/views/admin/students/index.blade.php`
   - `resources/views/admin/users/index.blade.php`
   - `resources/views/super/schools/{index,create,edit}.blade.php`
   - `resources/css/app.css`
2) To revert, restore these files from your last known good copy (or source control, if available).
3) Clear caches: `php artisan view:clear` and `php artisan cache:clear`.
4) Re-verify the routes in the testing checklist.

## Known remaining work (nice-to-have)
- Propagate `x-page-header` to other admin/role pages for full consistency.
- Replace bespoke card shadows/roundings with `.ui-card` / `.ui-glass` across remaining dashboards.
- Unify action buttons to a small set of variants/tokens.
- Audit welcome/guest pages for background consistency with the new shell background.
