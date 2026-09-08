# Access Hub — Requirements

A small central system that holds **who has what org role**, so that every other system can pull that list instead of an admin typing the same people into ten different access panels.

---

## 1. What this is and is not

**It is:** one list of people and their org role (requestor, division head, VP), plus a way for registered projects to pull that list on demand.

**It is not:** a login system. Every project keeps using the existing external Auth API exactly as it does today. The hub never sees a password, never issues a session, and is never called during login.

**Nothing breaks if the hub is down or was never set up.** A project that has never enrolled behaves exactly as it does now — admin ticks boxes by hand. The hub only adds a "Sync from hub" button. This is a convenience feature, not a dependency.

---

## 2. The problem being solved

Every system already has the same access panel and the same handful of roles. Today, granting access means:

- New system built → retype the same 12 people into it.
- New division head hired → open 10 systems, add them to each.
- Server rebuilt / database reset → retype everything again.

With the hub, the person is stated **once**. Each project pulls them in with one click.

---

## 3. Roles

The hub uses a **fixed, short list** of org roles. Projects do not invent their own role names in the hub.

| Role | Meaning |
|---|---|
| `requestor` | Manager / supervisor |
| `division_head` | Division head |
| `vp` | Vice president |
| `user` | Plain user, no elevated access |

Anything unusual (one or two special permissions in a single system) stays a manual entry in that project. Do not try to model those here — that is what keeps the hub small.

A person holds **one or more** of these roles. Most people have exactly one; someone who
is, say, both a requestor and a division head gets both, and every project receives the
full set and translates each to its local access. This is still a fixed vocabulary — it is
not a per-project role list or a permission bundle (see §10). Truly system-specific
permissions still stay a manual entry in that project.

---

## 4. Scope: global by default, per-project when needed

Global is the default because that is the common case.

**Per person — scope setting:**
- `all` — applies to every registered project. This is the default.
- `selected` — applies only to specific projects, picked from the registered list.

**Per project — acceptance setting:**
- `open` — accepts everyone whose scope is `all`, plus anyone pointed at it explicitly. Default.
- `explicit` — ignores `all`-scoped people, only accepts people who named this project. For sensitive systems where a new VP should not silently appear.

When a project syncs, the hub works out the intersection of these two settings and returns the resulting list.

### Farm and department are not access rules

Farm, department, and position have **no bearing on access anywhere**. Access is decided by `roles` and `scope` alone. These three fields exist so that people are identifiable — in the hub's own list, and more importantly in each system's access panel, where "Juan Cruz — Farm B, Operations" is far more useful than "Juan Cruz".

Their only functional use in the hub is filtering the admin list:

- Filter the people list by farm, department, role, or any combination.
- Bulk-apply a scope change to whatever the filter returned — e.g. filter to "Farm A, division heads", then point all of them at a new project in one action.

That is a convenience for the admin picking people. The filter narrows what is on screen; the admin still decides. Nothing evaluates farm or department at sync time, and nothing should ever be built that does — that would make access silently depend on hand-maintained data that goes stale.

---

## 5. Data model

Four tables. Keep it this small.

### `people`
| Column | Notes |
|---|---|
| `user_id` | The numeric ID from the org user directory. This is the join key every project already uses. |
| `name`, `email` | Cached from the directory for display. |
| `farm` | Which farm / site they belong to. Nullable. |
| `department` | Their department. Nullable. |
| `position` | Their actual job title, e.g. "Senior Accountant". Nullable. Purely descriptive — see below. |
| `roles` | JSON array. One or more of the fixed access roles above. |
| `scope` | `all` or `selected`. |
| `active` | Boolean. Set false on departure instead of deleting, so the history survives. |
| timestamps | |

**Position is not role.** Position is what the person's job is called; roles are what access level they get. They usually correlate but not always, and conflating them is how this turns back into per-person guesswork. Two people with the position "Farm Supervisor" might have different roles, and that must stay possible.

**Farm, department, and position never affect access.** They are display data, carried so that people are identifiable in the panels. Nothing in the hub or in any project should read them to decide anything.

**Where they come from:** the directory API does not return them — it only gives ID, name parts, and email. The hub owns this data and someone maintains it by hand. That means it can go stale: a transfer between farms will not appear automatically. That staleness is harmless precisely because nothing depends on them. Treat all three as nullable everywhere.

### `projects`
| Column | Notes |
|---|---|
| `key` | Short slug, e.g. `hrms`. |
| `name` | Display name. |
| `acceptance` | `open` or `explicit`. |
| `active` | Boolean. |
| timestamps | |

### `person_project`
Join table, only used when a person's scope is `selected`.

### `connections`
One row per **project per environment**. This is where enrollment lands.

| Column | Notes |
|---|---|
| `project_id` | |
| `environment` | `local`, `staging`, `production` — supplied by the project at enrollment. |
| `client_id` | Public identifier. |
| `client_secret_hash` | Hashed. Never retrievable after issue. |
| `last_seen_at` | Stamped on every successful sync — lets you see which connections are stale or dead. |
| `revoked_at` | Nullable. |
| timestamps | |

### `audit_log`
Append only. Every grant, role change, scope change, project registration, code issue, enrollment, and connection revoke. Record who did it, when, and from what IP.

This log is valuable on its own — right now there is zero visibility into who granted whom access across all the systems.

---

## 6. Enrollment — the one-time code

The goal is that a project admin never has to carry a secret between environments. No `.env` editing for this feature.

**Flow:**

1. Hub admin opens a project, clicks "Generate connection code". Hub shows something like `HUB-7F3K-QX92`.
2. Project admin opens their own access panel, clicks "Sync from hub", gets a modal asking for the code, pastes it.
3. Project sends the code to the hub along with its project key and environment name.
4. Hub validates the code, burns it, creates a `connections` row, and returns `client_id` + `client_secret` **once**.
5. Project stores those in **its own database**, not in `.env`.
6. Every sync after that uses the stored credentials. No more modals.

**Rules on the code:**
- Single use. Burned on first successful exchange.
- Short lifetime — 15 minutes.
- High entropy, unguessable.
- Scoped to one project.
- The enroll endpoint is rate limited and logs the source IP.
- The secret is returned once and stored hashed. Rotation means issuing a new one, not looking the old one up.

**Per environment:** local, staging, and production each have their own database, so each enrolls separately with its own code. That is intentional — you usually do not want staging pulling live grants. The hub lists them separately so you can revoke staging without touching production.

**Redeploys:** if the database survives, the connection survives and nothing needs re-entering. Only a fresh database needs a new code, which is one modal.

---

## 7. API

Two endpoints. That is all.

### `POST /api/v1/enroll`
Body: `code`, `project_key`, `environment`.
Returns: `client_id`, `client_secret`.
No auth (the code is the auth). Rate limited.

### `GET /api/v1/grants`
Auth: client credentials in headers.
Returns a flat list, one entry per person that applies to the calling project:

```json
{
  "generated_at": "2026-09-07T10:00:00Z",
  "people": [
    {
      "user_id": 412,
      "name": "Maria Santos",
      "email": "m.santos@example.org",
      "farm": "Farm A",
      "department": "Finance",
      "position": "Senior Accountant",
      "roles": ["division_head", "requestor"],
      "active": true
    },
    {
      "user_id": 87,
      "name": "Juan Cruz",
      "email": "j.cruz@example.org",
      "farm": "Farm B",
      "department": "Operations",
      "position": null,
      "roles": ["requestor"],
      "active": true
    }
  ]
}
```

**Send inactive people too**, with `active: false`. The project needs to know someone was removed — if you simply omit them, the project cannot tell "removed" from "never was here".

`farm`, `department`, and `position` are sent for display. Any of them can be null. Projects must not depend on them.

Stamp `last_seen_at` on the connection on every successful call.

---

## 8. Populating the people list

The hub reads the same external user directory the projects already use, so admins pick from a real list instead of typing IDs.

### Calling the directory

Same API as the auth systems' User Management panel. Config comes from `config/services.php`:

```php
'user_api' => [
    'endpoint' => env('USER_API_ENDPOINT', ''), // https://<auth-server>/api/v1/users
    'key'      => env('USER_API_KEY', ''),
],
```

| | |
|---|---|
| Method | **POST**, no body (not GET — this is the bulk list, not the `GET /api/v1/users/get-user-id` single lookup) |
| Auth | header `x-api-key: <USER_API_KEY>` |
| TLS | `->withOptions(['verify' => storage_path('cacert.pem')])` — copy `cacert.pem` from any existing system's `storage/` |
| Timeouts | `->timeout(10)->connectTimeout(5)` — a hung directory must never hang the page |

### Response shape — verify against the real API before writing the consumer

Bare array, **no `data` wrapper**, and **no single `name` field**:

```json
[
    {
        "id": "eyJpdiI6...==",          // encrypted → decrypts to the numeric user_id
        "first_name": "Maria Christina",
        "last_name": "Santos",
        "middle_name": null,
        "email": "m.santos@example.org",
        "created_at": null,              // nullable on old records
        "updated_at": "2026-07-14T05:45:08.000000Z"
    }
]
```

Consumer rules:

- Compose the display name: `trim(first_name . ' ' . last_name)`, with a fallback to a `name` key in case the API is ever normalized.
- Handle `$json['data'] ?? $json` anyway — cheap insurance.
- `created_at` can be null; don't format it blindly.
- Decrypt the ID with `Crypt::decryptString($user['id'])` before any DB work. Wrap each record in try/catch and skip on failure — a foreign `APP_KEY` throws per-record.

### Debug route first

Before writing any consumer code, stand up a temporary admin-gated, non-production route that dumps the raw response plus a decrypt check of the first record's ID. An `APP_KEY` mismatch is silent — every record just gets skipped — and this surfaces it in one request. Delete the route before go-live.

### `APP_KEY` must match the directory system

The encrypted IDs are encrypted with the directory / auth system's `APP_KEY`. The hub can only decrypt them if it runs with **the same `APP_KEY`**. In this org all systems already share one key for app-to-app login, so the hub simply inherits it — that shared key is what makes the directory read possible.

- Deploy the hub with the **byte-identical** `APP_KEY` (the full `base64:...` value) used by the directory system, per environment.
- **Do not run `php artisan key:generate`** on the hub after deploy. It breaks directory sync and nothing else, so it fails silently in the same way — an empty people list, no error.
- The debug-route-first check above is the guardrail: confirm record #1 decrypts before building anything on top.
- Sharing the key also means the hub *could* forge an app-to-app login into any other system. The hub never participates in login, so it never uses this — but do not add anything that would. See [§9](#9-security); fixing the shared-key problem is explicitly out of scope here.

If you fake this API in tests, build the fixture from a **pasted real response**, not from this document.

---

## 9. Security

The hub decides access for every system, so it needs to be the most locked down thing you run.

- Admin only. No general user access at all.
- Turnstile on the login form — this is the case where it earns its keep.
- IP allowlist if feasible.
- Full audit log, append only.
- **Block self-revoke.** An admin removing their own hub access locks everyone out.
- Keep one break-glass admin account documented offline.
- Client secrets stored hashed, transmitted only over HTTPS, never in a URL.
- Optionally bind each connection to an expected domain.

**Note on `APP_KEY`:** the existing systems all share one `APP_KEY` for app-to-app login, which means any one system can forge a login into any other. That is a separate problem, but the hub is the natural place to fix it later. Not in scope now.

---

## 10. What is deliberately excluded

- **No push / webhooks.** Sync is pull only, triggered by a button. Promotions are rare enough that this is fine, and it removes the most fragile part of the design (retry queues, signature verification, dead endpoints).
- **No hub involvement at login.** A hub outage must never stop anyone logging in.
- **No per-project role vocabularies in the hub.** Projects translate on their side.
- **No access rules based on farm, department, or position.** Those fields are display data only. Making access depend on hand-maintained org data that goes stale is a bug waiting to happen.
- **No permission bundles.** The hub says `["division_head"]`, not a list of module flags. What that means locally is the project's business. (A person may carry more than one role, but each is still one of the four fixed names — not an expanded permission set.)

### Accepted trade-off
Revocation has the same lag as promotion — someone marked inactive keeps access in a project until that project syncs. Mitigation is the "last synced N days ago" line in each project's panel (see the integration guide), plus a habit of syncing during offboarding. If this becomes a real problem, push can be added later; a clean pull implementation makes that easy.

---

## 11. Build order

1. Projects table + registration UI.
2. People list, populated from the directory API.
3. Scope settings (person and project).
4. Connection codes + `POST /enroll`.
5. `GET /grants`.
6. Audit log.
7. Connection management screen (list by project and environment, revoke, regenerate).

Build the **project side first** against a hand-written fake response (see the integration guide). If the preview and apply flow works cleanly in one system, the hub is just a form over a table.

---

## 12. Checklist

- [x] Four tables: `people`, `projects`, `person_project`, `connections`, plus `audit_log` (+ `connection_codes`, `access_logs`)
- [x] Directory API integration — `DirectoryClient` + `/debug/user-api` route (real client; "Add person" degrades cleanly when `USER_API_ENDPOINT` unset)
- [x] `APP_KEY` deployed identical to the directory system per environment; `key:generate` not run post-deploy
- [x] Project registration with `acceptance` setting
- [x] Person management with fixed role list (multi-select — a person can hold more than one) and `scope` setting
- [x] Farm, department, position fields — nullable, maintained in the hub (lists in `config/access-hub.php`)
- [x] Filter the people list by farm / department / role, with bulk scope actions on the result
- [x] Connection code generation — single use, 15 min, per project
- [x] `POST /api/v1/enroll` — rate limited (10/min), logs IP, returns secret once — see `docs/api.md`
- [x] `GET /api/v1/grants` — client auth (`X-Client-Id` / `X-Client-Secret`), returns inactive people too, stamps `last_seen_at`
- [x] Connections screen — grouped by project and environment, revoke and regenerate
- [x] Audit log on every mutating action (`App\Support\Audit`)
- [x] Admin-only access, self-revoke blocked. **Login is built-in Laravel auth** (email + password against `users`), not the external Auth API — Turnstile is wired but off unless `TURNSTILE_VERIFY=true`. 3-strikes/15-min lockout + `access_logs`.
- [x] Break-glass account — `AdminSeeder` from `BREAKGLASS_ADMIN_*`; document the credentials offline

### Not built (deferred)

External Auth API login flow, IP allowlist, domain-bound connections (`connections.domain`
column exists, unused), push/webhooks.
