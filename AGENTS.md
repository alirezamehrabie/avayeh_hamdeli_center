# Repository Guidelines

## Project Structure & Module Organization
This repository is a Laravel 11 application with a Vite-powered frontend. Core business logic lives in `app/`, with Livewire components in `app/Livewire`, Eloquent models in `app/Models`, HTTP entry points in `app/Http`, and reusable helpers, rules, and traits in their matching `app/*` folders. Routes are defined in `routes/web.php` and `routes/console.php`. Blade views, Livewire templates, and frontend assets live in `resources/views`, `resources/js`, `resources/css`, and `resources/sass`. Database migrations, factories, and seeders are under `database/`. Tests are split between `tests/Feature` and `tests/Unit`.

## Build, Test, and Development Commands
- `composer install` - install PHP dependencies.
- `npm install` - install frontend tooling used by Vite, Tailwind, and Bootstrap.
- `php artisan serve` - run the Laravel app locally.
- `npm run dev` - start the Vite dev server for asset rebuilding.
- `composer run dev` - run Laravel and Vite together with one command.
- `npm run build` - build production assets.
- `php artisan test` - run the PHPUnit suite.

## Coding Style & Naming Conventions
Follow `.editorconfig`: UTF-8, LF line endings, and 4-space indentation for PHP and most project files; YAML uses 2 spaces. Use PSR-4 namespaces that mirror paths, for example `App\Livewire\People\...`. Keep class names descriptive and singular where appropriate (`PersonExport`, `OperatorReportTest`). Blade and Livewire view names should stay kebab-case or folder-based to match their feature area. Format PHP with `./vendor/bin/pint` before opening a PR.

## Testing Guidelines
Write unit tests in `tests/Unit` for isolated logic and feature tests in `tests/Feature` for routes, Livewire flows, and reporting behavior. Name test files with the `*Test.php` suffix and group methods around user-visible behavior. Run `php artisan test` before committing; when working on reports or exports, add regression coverage similar to `tests/Feature/OperatorReportTest.php`.

## Commit & Pull Request Guidelines
Recent history uses short, imperative commit subjects such as `Update Details UI` and `Optimizing social worker search field`. Keep commits focused, start with a verb, and mention the feature area. Pull requests should include a brief problem/solution summary, linked issue or task reference, testing notes, and screenshots for UI changes.

## Security & Configuration Tips
Start from `.env.example` and keep secrets out of version control. Confirm queue, cache, and database settings before deploying, especially if switching away from the default local SQLite setup.
