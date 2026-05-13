# Unbeatentrack — how the site is built (for AI context)

## Purpose (inferred from code)

An **admin-first** Laravel app for curating **Australian towns** and **POIs** (points of interest): spreadsheets, photos, rich “About” HTML, publication/verification fields, and an **admin map** preview. The **public home page** is a simple Blade view (`welcome`); most product work lives under **`/admin`**.

## Core stack

| Layer | Choice |
|--------|--------|
| Runtime | **PHP ^8.3** |
| Framework | **Laravel ^13.7** (`laravel/framework`) |
| DB default | **SQLite** (see `.env.example`; `phpunit.xml` uses `:memory:` for tests) |
| Sessions / cache / queue | **Database** drivers in `.env.example` (`SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`) |
| HTML sanitization | **`symfony/html-sanitizer`** (used for safe “About” HTML) |
| Frontend build | **Vite 8** + **Laravel Vite plugin 3** |
| CSS | **Tailwind CSS 4** via **`@tailwindcss/vite`** |
| Fonts | **Bunny Fonts** wired through `laravel-vite-plugin` fonts helper — **Instrument Sans** in `vite.config.js` (weights 400–600). `resources/css/app.css` also references **Inter** in `@theme`. |
| JS entry | `resources/js/app.js` is effectively empty; UI is **server-rendered Blade**, not a SPA. |

## How to run locally (from `composer.json`)

- **`composer run setup`**: `composer install`, copy `.env` if missing, `key:generate`, `migrate --force`, `npm install`, `npm run build`.
- **`composer run dev`**: `concurrently` runs **`php artisan serve`**, **`queue:listen`**, **`pail`**, and **`npm run dev`** (Vite HMR).
- **`composer run test`**: clears config cache then **`php artisan test`** (PHPUnit 12).

Deployment note: a path like `Apache24\htdocs\unbeaten-track` suggests **Apache + PHP** in production is plausible; Laravel still expects the usual **`public/` document root**, built assets from **`npm run build`**, and **`php artisan storage:link`** for uploaded public files (called out in `.env.example`).

## Routing (`routes/web.php` + `bootstrap/app.php`)

- **`GET /`**: `welcome` view — public landing.
- **Auth**: `GET|POST /login` (guest middleware; POST throttled `10,1`), **`POST /logout`** (auth). **`LoginController`** rejects users who **cannot access the admin panel** even with valid credentials.
- **`/admin/*`**: prefix group with middleware **`auth`** + **`admin.panel`** (alias → `EnsureUserCanAccessAdmin`). Redirects: guests → `login`; authenticated users after login → **`admin.dashboard`** if `canAccessAdminPanel()`, else **`home`** (`bootstrap/app.php`).
- **No `routes/api.php`** in this app’s bootstrap — routing is **web + console + `health: /up`** only.

## Authorization model

- **`App\Models\User`**: `role` is `super_admin` or `admin` (constants on model). **`name`** is assembled on save from `first_name` / `last_name` (or email fallback).
- **`config/unbeaten_auth.php`**: human labels, `admin_panel_roles` list, and optional **`AdminBootstrapSeeder`** env-driven bootstrap users (`AUTH_BOOTSTRAP_*` in `.env.example`).
- **Gates**: `superAdmin` defined in `AppServiceProvider` → used to guard **`admin.users`** resource (super admins only).
- **Middleware**: `EnsureUserCanAccessAdmin` → **403** if not logged in or role not in `admin_panel_roles`.

## Domain data (high level)

- **`Town`**: geography, amenities flags, editorial fields, **`about_html`**, publication (`status`, `published_at`, `published_by`) and verification fields; relations include **photos** and **POIs**. Boot logic sets country default from **`config/australia_geography`** and stamps publish/verify metadata when status changes.
- **`Poi`**: belongs to a town; **categories** (array), **verification** enum, coordinates, **`about_html`**, publication fields; cascades photo deletes.
- Supporting models/controllers exist for **town photos**, **POI photos**, and spreadsheet-driven imports.

## Admin features (where complexity lives)

- **CRUD**: `TownController`, `PoiController` (resource routes except `show`).
- **Town map**: `GET admin/towns/map` + **`MapController`**: MapTiler style JSON is **fetched server-side**, **API keys stripped**, cached; tile/glyph requests go through **`admin/maps/proxy`** with **base64 `target`** allowlisted to MapTiler hosts — keys never ship to the browser.
- **AI “About” drafts**: `TownAboutAiDraftController` + `TownAboutAiDraftService`; POI analogue exists (`PoiAboutAiDraftController` / service). Config in **`config/town_ai.php`**: provider `auto|anthropic|openai`, models, and **heredoc default system prompts** (overridable via `.env` as documented there).
- **Imports**: Artisan **`towns:import-starter`** and **`pois:import-starter`** (`ImportStarterTownsCommand`, `ImportStarterPoisCommand`) delegate to **`TownSpreadsheetImportService`** / **`PoiSpreadsheetImportService`** and **`App\Support\Xlsx\*WorkbookReader`**. Default XLSX paths in commands may point at a sibling **`unbeaten\Data\...`** tree — environment-specific; use `--vic=` / `--nsw=` or change paths for your machine.

## Frontend / design system

- **`resources/css/app.css`**: large **design token** block (brand greens, neutrals, component patterns) + Tailwind 4 `@source` globs for Blade and JS.
- **`resources/views`**: Blade layouts including **`layouts/admin.blade.php`**; pagination uses custom views **`pagination.unbeaten`** / **`unbeaten-simple`** (registered in `AppServiceProvider`).

## Configuration worth knowing

- **`.env.example`**: documents `APP_URL`, MapTiler (`MAPTILER_*`), AI keys (`OPENAI_*`, `ANTHROPIC_*`, `TOWN_ABOUT_AI_PROVIDER`), bootstrap users, storage link reminder.
- **`config/town_ai.php`**, **`config/unbeaten_auth.php`**, **`config/maptiler.php`** (referenced by `MapController`), **`config/poi_taxonomy.php`**, **`config/town_verification.php`**, **`config/data/australia_regions_by_state.php`** — product rules and geography helpers.

## Tests

PHPUnit suites under **`tests/Unit`** and **`tests/Feature`** (e.g. admin auth, towns map, AI draft tests where present). Testing env forces in-memory SQLite, array session, sync queue, etc. (`phpunit.xml`).

## What this is *not* (today)

- Not a separate React/Vue SPA; **Vite is for CSS (and optional small JS)**.
- No first-class public town/POI browse API surfaced in `routes/web.php` beyond the welcome page — the app reads as **internal admin tooling** with a minimal public shell.
