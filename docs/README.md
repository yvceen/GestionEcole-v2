# Project Scan Report

This report summarizes a read-only scan of the repository with focus on build/run blockers, security risks, and configuration gaps. The detailed findings are in `docs/ISSUES.md`.

## Critical Issues (Top Table)

| ID | Severity | Area | File | What | Fix |
| --- | --- | --- | --- | --- | --- |
| ISS-001 | CRITICAL | Config | `.env` | Missing runtime environment file; Laravel cannot boot without `APP_KEY` and DB settings. | Copy `.env.example` to `.env`, set values, run `php artisan key:generate`. |
| ISS-002 | CRITICAL | Runtime | `php` | Zero-byte file named `php` in repo root shadows the PHP executable; `php -v` fails with Access denied. | Delete/rename the file, ensure `C:\xampp\php` is on PATH, re-run `php -v`. |
| ISS-003 | HIGH | Build | `vite.config.js` (and `node_modules/esbuild`) | `npm run build` fails: `Error: spawn EPERM` when esbuild starts. | Allow esbuild execution (AV/defender), or `npm rebuild esbuild` / reinstall `node_modules`. |
| ISS-004 | HIGH | Security | `.env.testing:22`, `phpunit.xml:31` | Hardcoded DB password committed in repo. | Remove secrets from tracked files; use env vars or `.env.testing.example`. |
| ISS-005 | MEDIUM | Deploy | `public/build/manifest.json` | Missing Vite manifest; production asset loading will fail. | Run `npm run build` and deploy `public/build/`. |

## Quick Win Fixes (Top 5)

1. Remove the stray zero-byte `php` file in the repo root so `php` resolves to the real PHP executable.
2. Create `.env` from `.env.example` and generate a new app key.
3. Fix the `npm run build` `spawn EPERM` by allowing esbuild to execute (Windows Defender/AV), then rebuild.
4. Replace hardcoded test DB credentials in `.env.testing` and `phpunit.xml` with environment variables.
5. Build assets and ensure `public/build/manifest.json` exists before deploying.

## Reproduction Steps

These commands reproduce the observed failures on this machine:

```powershell
php -v
npm run build
```

See `docs/ISSUES.md` for full details and fixes.
