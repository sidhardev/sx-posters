# sx-posters

Poster SaaS MVP built with **PHP + SQLite + Tailwind CSS**, based on `goal.md`.

## Features

- User auth (phone + password)
- Business profile with auto-fill on next login
- Admin login (role-based user from DB)
- Admin template management (add/list, preview upload, category, prompt)
- Gemini-powered poster generation with variable injection:
  - `{business_name}`
  - `{business_type}`
  - `{phone}`
- Generated poster output:
  - PNG download
  - WhatsApp share link
- SQLite tables: `users`, `business_profiles`, `templates`, `posters`

## Setup

1. Copy env file:

```bash
cp .env-example .env
```

2. Update `.env` with your real values:

- `GEMINI_API_KEY`
- `GEMINI_MODEL`
- `GEMINI_SYSTEM_PROMPT` (optional custom style/system instruction)
- `ADMIN_SEED_PHONE`
- `ADMIN_SEED_PASSWORD`
- `APP_URL` (for WhatsApp share links)

Admin seed credentials are used to create (or promote) a user to `admin` on first load.

3. Start PHP server:

```bash
php -S 0.0.0.0:8000
```

4. Open:

- User app: `http://localhost:8000/`
- Admin panel: `http://localhost:8000/?page=admin-login`

## Notes

- SQLite DB is created automatically at `database/app.sqlite`.
- Upload folders are under `uploads/`.
- Ensure PHP has `pdo_sqlite`, `curl`, and `fileinfo` enabled.
- For production, add rate limiting and stronger login protection (e.g., OTP/2FA) for phone-based authentication.
- For production, enforce HTTPS and hardened session cookie settings (`Secure`, `HttpOnly`, `SameSite`).
- For production, prefer storing `ADMIN_SEED_PASSWORD` as a `password_hash()` value in `.env`.
- For production, rotate CSRF/session secrets periodically and set strict security headers (including CSP).
