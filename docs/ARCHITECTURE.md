# Architecture

## High-Level Overview
- **Backend**: Laravel 12 app in `app/`, routes in `routes/`, config in `config/`, migrations/seeders in `database/`.
- **Frontend**: Vite builds assets from `resources/` into `public/build/`.
- **Mobile**: Capacitor project in `android/` with web assets in `www/`.

## Key Modules
- `app/Http/Controllers/` — MVC controllers (Admin, Director, Parent, Student, Teacher, SuperAdmin areas).
- `app/Models/` — Eloquent models for domain entities (students, courses, payments, transport, etc.).
- `resources/views/` — Blade templates.
- `resources/js/` and `resources/css/` — Frontend entry points for Vite.
- `routes/` — Laravel route definitions (web/api/console).

## Data Layer
- Migrations in `database/migrations/` define schema.
- Seeders in `database/seeders/` load initial data.

## Build/Tooling
- `vite.config.js` uses `laravel-vite-plugin`.
- `tailwind.config.js` and `postcss.config.js` manage styling.
- `composer.json` and `package.json` define backend/frontend dependencies.
