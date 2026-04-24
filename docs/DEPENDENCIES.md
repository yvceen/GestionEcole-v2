# Dependencies

## Package Managers
- Composer (PHP)
- npm (Node)

## Composer Dependencies
From `composer.json`:

Require:
- `php` ^8.2
- `laravel/framework` ^12.0
- `laravel/tinker` ^2.10.1

Require-dev:
- `barryvdh/laravel-debugbar` ^4.0
- `fakerphp/faker` ^1.23
- `laravel/breeze` ^2.3
- `laravel/pail` ^1.2.2
- `laravel/pint` ^1.24
- `laravel/sail` ^1.41
- `mockery/mockery` ^1.6
- `nunomaduro/collision` ^8.6
- `phpunit/phpunit` ^11.5.3

Install steps:
```powershell
composer install
```

Notes:
- `vendor/` exists, so dependencies appear installed.
- `php` must resolve to the real PHP executable (see ISS-002).

## npm Dependencies
From `package.json`:

Dependencies:
- `@capacitor/android` ^8.1.0
- `@capacitor/cli` ^8.1.0
- `@capacitor/core` ^8.1.0

DevDependencies:
- `@tailwindcss/forms` ^0.5.2
- `@tailwindcss/vite` ^4.0.0
- `alpinejs` ^3.4.2
- `autoprefixer` ^10.4.2
- `axios` ^1.11.0
- `concurrently` ^9.0.1
- `laravel-vite-plugin` ^2.0.0
- `postcss` ^8.4.31
- `tailwindcss` ^3.1.0
- `vite` ^7.0.7

Install steps:
```powershell
npm install
```

Notes:
- `node_modules/` exists.
- `npm run build` currently fails with `spawn EPERM` (see ISS-003).
