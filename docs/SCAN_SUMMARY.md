# Scan Summary

## Scope
- Full repository tree under `C:\xampp\htdocs\GestionEcole`.
- Read-only inspection of configuration, dependencies, and build tooling.

## Stack Detected
- Backend: Laravel 12 (PHP)
- Frontend: Vite + Tailwind CSS + Alpine.js
- Mobile: Capacitor + Android project
- Package managers: Composer, npm

## Environment (Detected)
- OS: Windows (PowerShell)
- Node: v20.20.0
- npm: 10.8.2
- PHP: 8.2.12 (via `C:\xampp\php\php.exe`)
- Composer: 2.9.3

## Commands Executed
```
C:\xampp\php\php.exe -v
composer -V
node -v
npm -v
npm run build
Get-ChildItem -Force
Get-ChildItem -File | Where-Object { $_.Length -eq 0 } | Select-Object Name, Length, FullName
Get-Content composer.json
Get-Content package.json
Get-Content .env.example
Get-Content .env.testing
Get-Content phpunit.xml
Get-Content vite.config.js
Test-Path .env
Test-Path public/build/manifest.json
rg -n "DB_PASSWORD" .env.testing
rg -n "APP_KEY" .env.testing
rg -n "DB_PASSWORD" phpunit.xml
tree /f /a
```

## Notes
- `git status -sb` failed because `.git` is not present (not a git repo here).
- `php -v` failed due to a zero-byte `php` file shadowing the real executable.
- `npm run build` failed with `Error: spawn EPERM` from esbuild.
