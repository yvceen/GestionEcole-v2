**UI Structure Overview**

Browser → `layouts/*` → `components/app-shell.blade.php` → `app-navbar` + sidebar (role layouts) → role page content → shared UI components → Tailwind (`resources/css/app.css`) & JS (`resources/js/app.js`, Vite).

**A) High-level UI Architecture**
```
Browser
 └─ layouts/app.blade.php (auth’d) / layouts/guest.blade.php / welcome.blade.php
     └─ <x-app-shell> (resources/views/components/app-shell.blade.php)
         ├─ Navbar: resources/views/components/app-navbar.blade.php
         ├─ Sidebar: injected via role layout (admin/teacher/parent/student/director/super) using <x-app-shell :links=...>
         └─ Main slot: role/layout slot -> page Blade -> components (cards, tables, forms)
CSS: resources/css/app.css + Tailwind config (tailwind.config.js)
JS: resources/js/app.js (Alpine, Vite entry), Vite config vite.config.js
Routes: routes/web.php maps dashboards to role layouts/components
```

**B) Layout Responsibility Table**
- `resources/views/layouts/app.blade.php`: Authenticated base; wraps everything in `<x-app-shell>`; no direct navbar/sidebar logic here; adds app-card wrappers for slot/header.
- `resources/views/layouts/guest.blade.php`: Guest pages (login/register); no app-shell; simple bg + container.
- `resources/views/components/app-shell.blade.php`: Global shell; holds navbar include, mobile overlay, desktop sidebar block, and main slot. Handles mobileOpen Alpine state and body scroll lock.
- `resources/views/components/admin-layout.blade.php`: Builds `$nav` (admin routes) then `<x-app-shell :links="$nav" navigation-title="Administration">` with page title slot inside.
- `resources/views/components/teacher-layout.blade.php`: Same pattern; title “Portail enseignant”.
- `resources/views/components/student-layout.blade.php`: Same; title “Portail eleve”.
- `resources/views/components/parent-layout.blade.php`: Same; title “Portail parent”.
- `resources/views/components/director-layout.blade.php`: Same; title “Portail direction”.
- `resources/views/components/super-layout.blade.php`: Same; title “Portail super admin”.
- `resources/views/profile/edit.blade.php` etc. use `<x-app-layout>` (Jetstream-style) which resolves to layouts/app.blade.php -> app-shell.

Slot injection:
- Role layouts: title passed to their own markup; main content provided by page wrapped in the layout component (Blade component slot).
- `app-shell`: receives `$links` and `$navigationTitle`, renders navbar + side + main slot.

Spacing wrappers in layouts:
- Body classes in role layouts: `app-shell-body min-h-screen overflow-x-hidden m-0 p-0`.
- `app-shell` main area: `min-w-0 flex-1 ... py-6 px-4 sm:px-6 lg:px-8`.
- Sidebar container in `app-shell`: `w-[280px] shrink-0` with inner padding `p-4`.

**C) Single Source of Truth (desired vs current)**
- Current owner of main flex + sidebar width: `components/app-shell.blade.php`.
- Navbar position: sticky in `components/app-navbar.blade.php` plus CSS `.app-navbar`.
- Page container width/padding: mostly in `app-shell` main (px-4 sm:px-6 lg:px-8 py-6); also many pages add their own `max-w-*` / `px-*`.
- Duplicates: page-level wrappers (e.g., many admin pages use `max-w-4xl mx-auto`, grids with `px-6`), and `app.css` defines `.app-navbar` sticky too—duplicating navbar stickiness with header’s own class.

**D) Navbar and Sidebar Details**
- Navbar file: `resources/views/components/app-navbar.blade.php`
  - Sticky header (`class="sticky top-0 ..."`); height ~64px via content; shadow toggled on scroll by Alpine.
  - Logo resolution: tries school-specific logo (storage/public), fallback `public/images/edulogo.jpg?v=3`.
  - Mobile menu button dispatches `toggle-mobile-sidebar` event handled in app-shell.
  - Quick links/actions per role; notifications dropdown polling via fetch.
- Sidebar: no dedicated component; built inline in `app-shell.blade.php` using `$resolvedLinks` passed from role layouts.
  - Desktop: `w-[280px] shrink-0`; glass gradient; scrollable nav inside.
  - Mobile: fixed drawer `left-0 top-0 h-full w-[280px] max-w-[85vw]` with overlay; closes on click.
  - Active link class: `bg-white/15 ... ring-1 ring-white/20 text-white` (dark glass variant).

**E) Spacing & Width Map**
- Global: `html, body { max-width:100%; overflow-x:hidden; margin:0; padding:0; }` (app.css). `body` min-h-screen.
- Navbar: sticky top-0; app.css also sets `.app-navbar` sticky, top:0, with forced margin/padding reset—risk of double enforcement.
- App-shell: `flex flex-1` wrapper; main: `min-w-0 flex-1 px-4 sm:px-6 lg:px-8 py-6 overflow-x-hidden`.
- Sidebar: desktop width `w-[280px] shrink-0`; inner padding `p-4`; no top offset now (sits beneath navbar due to normal flow).
- Overflows: many tables/cards add `overflow-x-auto` per page; shell enforces `overflow-x-hidden` on main to prevent horizontal scroll.
- Common page paddings: frequent `max-w-7xl mx-auto px-4 sm:px-6` (welcome, some admin lists); dashboards use `grid ... gap-*`.
- Potential break points:
  - Changing navbar height without adjusting sticky offsets in sidebar (none now; previously top-[80px] risk).
  - Removing `min-w-0` on main causes flex children to overflow horizontally.
  - Tweaking body overflow-x-hidden could reintroduce horizontal scroll from wide tables.
  - Sidebar width change requires adjusting any `lg:grid-cols-[1fr_280px]` patterns in admin pages (e.g., transport routes edit/create) to avoid wrapping.

**F) Component Inventory (UI library)**
- Navigation: `components/app-navbar.blade.php`, sidebar markup in `components/app-shell.blade.php`.
- Layout wrappers: `components/*-layout.blade.php`, `layouts/app.blade.php`, `layouts/guest.blade.php`, `components/app-shell.blade.php`.
- Cards/panels: utility classes in `app.css` (`glass-panel`, `app-card`); pages use bespoke rounded-[28px]/rounded-[24px] panels.
- Tables: `.app-table` styles in app.css; many admin pages use Tailwind tables with `overflow-x-auto`.
- Form controls: `.app-input`, page-level `rounded-2xl border ...` inputs.
- Alerts/Badges: `.app-badge-*`, page-specific alert boxes.
- Buttons: `.app-button-primary`, `.app-button-secondary`, plus page-local buttons.
- Messaging composer: `resources/views/components/messaging-composer.blade.php` (not scanned fully, but present from audit docs).

**G) Known UI Issues (current)**
- Sticky duplication: Both header class and `.app-navbar` CSS set sticky/top-0; conflicting tweaks can cause unexpected offsets.
- Global “top-gap guard” in `resources/css/app.css` forcibly zeroes margin/padding on headers/navs—may mask root cause rather than fix it; changing heights can still shift content.
- Sidebar built inline per shell; no single source for width/spacing; glass dark variant may contrast poorly on some pages.
- Pages with their own `max-w` and `px` (e.g., transport edit/create) can misalign with shell padding; altering shell paddings risks double gutters.
- Overflow risk: removing `min-w-0` or `overflow-x-hidden` on main would let wide tables push layout.
- Mobile drawer shares same markup as desktop (duplicated); adjustments must be applied twice.

**H) Recommendations (Staged)**
- Stage 1: Unify shell spacing—keep `min-w-0`, `overflow-x-hidden`, set a single source for main padding (app-shell main). Remove duplicate navbar CSS stickiness (pick header class or `.app-navbar`).
- Stage 2: Sidebar/navbar normalization—extract sidebar into its own component for reuse; define a single sidebar width token; ensure mobile/desktop share classes via includes to avoid divergence.
- Stage 3: Standardize page headers/tables—create shared page header component (title + actions); enforce table wrapper with `overflow-x-auto` and consistent padding/gaps; reduce scattered max-w/px by using section container helpers.

**Index of Key UI Files**
- Layout entry: resources/views/layouts/app.blade.php
- Guest layout: resources/views/layouts/guest.blade.php
- Shell: resources/views/components/app-shell.blade.php
- Navbar: resources/views/components/app-navbar.blade.php
- Role layouts: resources/views/components/admin-layout.blade.php, teacher-layout.blade.php, student-layout.blade.php, parent-layout.blade.php, director-layout.blade.php, super-layout.blade.php
- Styles: resources/css/app.css; tailwind.config.js; vite.config.js
- JS entry: resources/js/app.js
- Pages: resources/views/admin/**, teacher/**, student/**, parent/**, director/**, super/**

**Where to change X (quick guide)**
- Sidebar width/visuals: resources/views/components/app-shell.blade.php (desktop/mobile sidebar blocks).
- Navbar behavior (sticky/shadow/content): resources/views/components/app-navbar.blade.php; sticky fallback also in app.css `.app-navbar`.
- Main padding/overflow: main container in resources/views/components/app-shell.blade.php.
- Global spacing/overflow defaults: resources/css/app.css (html/body, app-shell-body).
- Logo source: resources/views/components/app-navbar.blade.php (schoolLogoUrl logic).
- Page container widths: adjust per-page wrappers (e.g., `max-w-*`/`mx-auto`) inside role pages under resources/views/*/**.
