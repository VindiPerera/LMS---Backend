# FaceTalk Backend

Laravel + MySQL API backend for the [FaceTalk Flutter app](../../LMSFrontend) — a language exchange app for students and teachers.

## Stack

- Laravel 13 (PHP 8.3)
- MySQL / MariaDB
- Laravel Sanctum (bearer token auth — no cookies/CSRF, works cleanly from a Flutter mobile/web client)

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```
2. Copy the env file and set your DB credentials:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Then edit `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=facetalk
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Create the database (MySQL/MariaDB must be running, e.g. via XAMPP):
   ```sql
   CREATE DATABASE facetalk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start the dev server:
   ```bash
   php artisan serve
   ```
   API is now available at `http://127.0.0.1:8000/api`.

## API Endpoints

| Method | Endpoint         | Auth | Description                                  |
|--------|------------------|------|-----------------------------------------------|
| POST   | `/api/register`  | No   | Create account (name, email, password, role) |
| POST   | `/api/login`     | No   | Log in, returns bearer token                 |
| POST   | `/api/logout`    | Yes  | Revoke current token                         |
| GET    | `/api/me`        | Yes  | Current authenticated user                   |
| PUT    | `/api/profile`   | Yes  | Update profile (bio, languages, etc.)        |

Authenticated requests need `Authorization: Bearer <token>`.

### `role`

Must be `student` or `teacher` (matches `FaceTalkRole` in the Flutter app's signup screen).

### User JSON shape

Field names are camelCase to match `lib/models/user.dart` (`AppUser`) on the frontend directly — see `app/Http/Resources/UserResource.php`.

## Connecting the Flutter app

The frontend's API base URL lives in `LMSFrontend/lib/services/api_config.dart`:

- Chrome/web: `http://127.0.0.1:8000/api` (default — talks to localhost directly)
- Android emulator: change to `http://10.0.2.2:8000/api` (emulator's alias for host localhost)
- Physical device on Wi-Fi: use your machine's LAN IP instead

## Notes

- CORS is wide open (`config/cors.php`) for local development. Tighten `allowed_origins` before deploying anywhere public.
- Passwords are hashed with bcrypt via Laravel's default `Hash` facade.
- On registration, a unique `@handle` is auto-generated from the user's name (e.g. "Vinuk Lakvindu" → `vinuk_lakvindu`).
