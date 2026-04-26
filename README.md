# sx-posters

Poster SaaS MVP built with **PHP + SQLite + Tailwind CSS**, based on `goal.md`.

## Features

- User auth (phone + password)
- Business profile with auto-fill on next login
- Admin login (phone/email + password from `.env`)
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
- `ADMIN_LOGIN_PHONE`
- `ADMIN_LOGIN_EMAIL`
- `ADMIN_PASSWORD`
- `APP_URL` (for WhatsApp share links)

3. Start PHP server:

```bash
php -S 0.0.0.0:8000 -t /home/runner/work/sx-posters/sx-posters
```

4. Open:

- User app: `http://localhost:8000/`
- Admin panel: `http://localhost:8000/?page=admin-login`

## Notes

- SQLite DB is created automatically at `database/app.sqlite`.
- Upload folders are under `uploads/`.
- Ensure PHP has `pdo_sqlite`, `curl`, and `fileinfo` enabled.
