# Issues

This file lists all detected issues. Each includes a concrete location, severity, and fix.

## Index
- ISS-001 Missing `.env` runtime config
- ISS-002 `php` executable shadowed by zero-byte file
- ISS-003 `npm run build` fails (esbuild spawn EPERM)
- ISS-004 Hardcoded DB password in test config
- ISS-005 Missing Vite manifest for production
- ISS-006 Stray zero-byte files in repo root
- ISS-007 Repository not a git working tree (uncertain impact)
- ISS-008 Capacitor Android config points to production URL (uncertain)

---

## ISS-001 Missing `.env` runtime config
- Severity: CRITICAL
- Area: Config/Runtime
- File: `.env` (missing)
- Why it’s a problem: Laravel requires `APP_KEY` and DB settings. Without `.env`, the app typically fails to boot with “No application encryption key has been specified.”
- Fix:
  - Copy `.env.example` to `.env`.
  - Set DB values and other secrets.
  - Run `php artisan key:generate`.
- Reproduction:
  - `php artisan serve` (will fail until `.env` exists).

## ISS-002 `php` executable shadowed by zero-byte file
- Severity: CRITICAL
- Area: Runtime/Tooling
- File: `php` (repo root, 0 bytes)
- Why it’s a problem: Running `php -v` in this directory attempts to execute the local `php` file, resulting in Access denied. This breaks artisan commands and composer scripts that rely on `php` in PATH.
- Fix:
  - Delete or rename the `php` file.
  - Ensure `C:\xampp\php` is on PATH.
- Reproduction:
  - `php -v` (currently fails with Access denied).

## ISS-003 `npm run build` fails (esbuild spawn EPERM)
- Severity: HIGH
- Area: Build
- File: `vite.config.js` (build entry) / `node_modules/esbuild`
- Why it’s a problem: `npm run build` fails to load Vite config due to `Error: spawn EPERM` when esbuild starts, blocking asset builds and production deploys.
- Fix (likely):
  - Allow `esbuild.exe` to run (Windows Defender/AV Controlled Folder Access).
  - Reinstall or rebuild esbuild: `npm rebuild esbuild`.
  - If needed, delete `node_modules` and `package-lock.json`, then `npm install`.
- Reproduction:
  - `npm run build`

## ISS-004 Hardcoded DB password in test config
- Severity: HIGH
- Area: Security/Config
- Files:
  - `.env.testing:22` (`DB_PASSWORD=123456789`)
  - `phpunit.xml:31` (`DB_PASSWORD` attribute)
- Why it’s a problem: Secrets committed to the repository can leak credentials and are commonly reused in other environments. Even if intended for tests, this is a risky pattern.
- Fix:
  - Remove credentials from tracked files.
  - Use environment variables or a `.env.testing.example` template.
  - Prefer SQLite for tests to avoid shared DB credentials.

## ISS-005 Missing Vite manifest for production
- Severity: MEDIUM
- Area: Deploy
- File: `public/build/manifest.json` (missing)
- Why it’s a problem: Laravel’s Vite helper expects the manifest in production. If absent, asset loading fails.
- Fix:
  - Run `npm run build` (after resolving ISS-003) and deploy `public/build/`.
- Reproduction:
  - Attempt to load a page in production mode without `public/build/manifest.json`.

## ISS-006 Stray zero-byte files in repo root
- Severity: LOW
- Area: Hygiene/Tooling
- Files:
  - `'A'])`
  - `as('admin.')`
  - `feePlan`
  - `first()`
  - `group(function`
  - `middleware(['auth'`
  - `name('approve')`
  - `name('create')`
  - `name('index')`
  - `name('pending')`
  - `name('reject')`
  - `name('show')`
  - `name('store')`
- Why it’s a problem: These look like accidental artifacts (possibly from shell redirection). They clutter the repo and may confuse tooling or scripts.
- Fix:
  - Remove these zero-byte files if they are not intentional.

## ISS-007 Repository not a git working tree (uncertain impact)
- Severity: LOW
- Area: Tooling
- File: `.git` (missing)
- Why it’s a problem: Git operations (status, diff, history) are unavailable. This may be intentional if this is an exported folder. Uncertain impact on build/runtime.
- Fix:
  - If this should be a git repo, re-clone or restore `.git`.

## ISS-008 Capacitor Android config points to production URL (uncertain)
- Severity: LOW
- Area: Mobile/Config
- File: `capacitor.config.json:7-16`
- Why it’s a problem: The Capacitor dev build will load `https://myedu.school` instead of local dev server. This can confuse local testing. This is not a functional error if production URL is intended.
- Fix:
  - For local dev, set `server.url` to your dev host or remove `server` block.
- Status: Uncertain — verify intended mobile workflow.
