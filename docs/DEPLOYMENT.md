# Deploying Access Hub

A single-server deployment. The hub is admin-only, has no public signup, and one
break-glass admin account is the whole user list.

---

## 1. Server requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `bcmath`
- MySQL 8 (database `accesshub_database`, a dedicated user)
- Composer (only if you build on the server — see step 3)
- A web server (nginx/Apache) with the document root at **`public/`**
- HTTPS (required — sessions are set `Secure`)

## 2. Upload

Upload the whole project folder **except** `node_modules/`. You must include:

| Path | Why |
|---|---|
| `vendor/` | PHP dependencies — or run `composer install` on the server (step 3) |
| `public/build/` | Compiled CSS/JS — or run `npm ci && npm run build` locally and upload the result |
| `storage/cacert.pem` | TLS bundle for the directory API call |

Do **not** upload your local `.env`.

After upload, make these writable by the web server user:

```sh
chmod -R ug+rwX storage bootstrap/cache
```

## 3. Dependencies (skip if you uploaded `vendor/` and `public/build/`)

```sh
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 4. Configure `.env`

```sh
cp .env.example .env
```

Then edit `.env`:

| Key | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://` + the real hostname |
| `APP_KEY` | **The directory system's key, verbatim.** `base64:...`. See note below. |
| `DB_*` | The MySQL database and a dedicated user |
| `BREAKGLASS_ADMIN_EMAIL` / `BREAKGLASS_ADMIN_PASSWORD` | The one admin. Required. |
| `USER_API_ENDPOINT` / `USER_API_KEY` | The org directory bulk endpoint + key |
| `TURNSTILE_VERIFY` / `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | `true` + both keys (recommended) |
| `SESSION_SECURE_COOKIE` | `true` |

> **`APP_KEY` must equal the directory system's key** — the directory returns user
> ids encrypted with *its* key, and the hub decrypts them with the same one. A
> mismatch is silent: every record is skipped and the people directory looks empty.
> **Never run `php artisan key:generate` on this server.**

## 5. Migrate and seed the admin

```sh
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
```

`DemoDataSeeder` never runs in production. `AdminSeeder` aborts if the break-glass
env vars are missing, and never overwrites an admin that already exists (safe to
re-run on redeploy).

## 5a. Load the personnel roster

```sh
php artisan db:seed --class=PersonnelSeeder --force
```

Reads `database/data/personnel-directory.md` (the org chart — who is a VP / division
head / manager / supervisor), matches each name to a directory-API user to get their
real `user_id`, then **wipes and repopulates** the `people` table — a clean reload, no
merge. Everyone lands with scope `all`; narrow individuals in the UI afterwards.

Needs the directory API reachable (it aborts, untouched, if not). Re-run it whenever
the org chart changes — just replace the four tables in that `.md` file first. The run
prints how many were seeded, any **low-confidence matches** (surname only — eyeball
them), and anyone **not found in the directory** (add by hand, or fix the spelling in
the file). It also writes a dated `people.roster_loaded` row to the audit log.

Role mapping: VP → `vp`, Division Head → `division_head`, Manager & Supervisor → `manager`.

## 6. Cache for production

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Re-run these after any future `.env` or code change (see step 8).

## 7. Verify

```sh
php artisan hub:check
```

Confirms: `APP_KEY` set, `cacert.pem` present, `APP_DEBUG` off, secure cookies on,
an active admin exists, the directory API is reachable, and its records **decrypt
with this server's `APP_KEY`** (prints a sample person). All lines must say `PASS`.

Then open `https://<host>/` — you should be redirected to `/login`. Sign in with the
break-glass admin and immediately change the password under **Account** (bottom of
the sidebar).

## 8. Redeploying later

```sh
php artisan down
# upload changed files
composer install --no-dev --optimize-autoloader   # if vendor changed
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

If you edit `.env` on the server, run `php artisan config:clear` (or re-cache) and
restart PHP-FPM — a running worker keeps the old values otherwise.

## 9. Cron (optional)

Nothing is scheduled today. If that changes, add:

```
* * * * * cd /path/to/accesshub && php artisan schedule:run >> /dev/null 2>&1
```

## 10. Web server notes

- Document root → `public/`
- `public/index.php` is the only entry point
- The queue driver is `database` but nothing dispatches jobs yet — no worker needed
- The two API routes are `POST /api/v1/enroll` and `GET /api/v1/grants`
  (see `docs/api.md`); both are rate-limited in-app

---

## The one admin

- `AdminSeeder` creates exactly one admin from `.env`.
- That admin **cannot deactivate themselves** (would lock everyone out).
- They can change their own password in-app (**Account**), add another admin, or
  deactivate a second admin — all from the **Admins** screen.
- Keep the break-glass credentials in `.env` and documented offline. If the
  password is ever changed in-app and then lost, reset it with:
  `php artisan tinker --execute="App\Models\User::where('email','…')->first()->update(['password'=>Hash::make('new-pw')])"`
