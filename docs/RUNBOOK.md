# Runbook

## Prerequisites
- PHP 8.2+ (XAMPP `C:\xampp\php\php.exe`)
- Composer 2.x
- Node 20+ and npm 10+
- MySQL server (for dev/test) or SQLite if you reconfigure

## One-Time Setup
```powershell
Copy-Item .env.example .env
C:\xampp\php\php.exe artisan key:generate
composer install
npm install
```

## Run Dev (Laravel + Vite)
```powershell
# Backend
C:\xampp\php\php.exe artisan serve

# Frontend (separate terminal)
npm run dev
```

Notes:
- If `php` fails, remove the stray `php` file in the repo root (ISS-002).
- Configure DB credentials in `.env` before running migrations.

## Build Assets (Production)
```powershell
npm run build
```

Expected output:
- `public/build/manifest.json` should exist.

## Database Migrations / Seed
```powershell
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan db:seed
```

## Tests
```powershell
C:\xampp\php\php.exe artisan test
```

Notes:
- Tests are configured to use MySQL (`DB_DATABASE=schoolapp_test`). Ensure DB exists and credentials are set.

## Deploy (Laravel)
1. Install PHP/Composer deps on server.
2. Build frontend assets (`npm run build`).
3. Ensure `.env` is set and `APP_KEY` generated.
4. Run `php artisan migrate --force`.
5. Configure web server (Apache/Nginx) to `public/`.

## Mobile (Capacitor)
```powershell
npm install
npx cap sync android
```

Note: `capacitor.config.json` points to `https://myedu.school` (ISS-008). For local dev, adjust `server.url`.
