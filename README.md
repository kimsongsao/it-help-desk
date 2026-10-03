# HelpDesk

A multi-tenant help desk for companies of every size, from a small café answering customer questions to an enterprise IT, HR or facilities department. Each company gets its own isolated workspace with tickets, SLAs, workflow automation, reports and an API, in **English and Khmer**.

Built by [Kdigitized Solutions](https://kdigitized.com).

---

## Contents

- [Features](#features)
- [Plans](#plans)
- [Tech stack](#tech-stack)
- [Getting started](#getting-started)
- [Demo accounts](#demo-accounts)
- [Configuration](#configuration)
- [Running in production](#running-in-production)
- [Architecture](#architecture)
- [REST API](#rest-api)
- [Localization](#localization)
- [Testing](#testing)
- [Roadmap](#roadmap)

---

## Features

### Tickets

- Guided ticket form: pick a topic, add a title, describe the problem, choose urgency.
- Describe a problem by typing, **dictating** (speech to text in English or Khmer), or recording a **voice note**.
- Drag and drop **attachments** (up to 5 files, 10 MB each). Files are stored privately and only served to people allowed to see the ticket.
- Conversation thread with **internal notes** that are hidden from the requester.
- Status workflow (Open → In Progress → On Hold → Resolved → Closed) with enforced transitions. A requester's reply reopens a resolved ticket.
- One-click agent actions: *Assign to me*, *Start working*, *Put on hold*, *Mark resolved*, *Reopen*.
- Activity timeline for every change.

### Service levels and escalation

- SLA policies by priority and/or category, with first response and resolution targets.
- 24/7 or business-hours calendars. The clock pauses while a ticket is On Hold.
- *At risk* and *breached* tracking, plus automatic escalation on breach (raise priority, hand over to a supervisor).
- Manual escalation with a reason and a full escalation history.

### Workflow automation

- Rules made of a trigger (ticket created or updated, reply added, SLA breached), conditions (all or any) and actions.
- Actions: set status, priority or category; assign to a person or the least busy agent; add an internal note; escalate.

### Reports

- Volume over time, SLA compliance, average first response and resolution times, and breakdowns by priority, category and agent.
- CSV export.

### Companies, people and permissions

- Self-service company sign-up with a starting template: General, IT support, Customer service, HR or Facilities.
- Employees join through the company's join link or an allowed email domain.
- Sign in with email and password, **Google**, **Facebook** or **Telegram**.
- Roles with 16 fine-grained permissions, editable per company.
- Platform console for the operator: every company, plan changes, suspension, onboarding.

### Experience

- Tabler UI with light and dark mode, responsive down to phone width.
- In-app and email notifications, delivered in each recipient's own language.

---

## Plans

Plans are enforced in the app. There is no payment integration yet; the platform admin assigns plans.

| | Free | Business | Enterprise |
|---|:---:|:---:|:---:|
| Support agents | 3 | 25 | Unlimited |
| Tickets, attachments, voice, knowledge base | ✓ | ✓ | ✓ |
| SLA policies & escalation | | ✓ | ✓ |
| Workflow automation | | ✓ | ✓ |
| Reports & analytics | | ✓ | ✓ |
| REST API | | ✓ | ✓ |
| Agent teams, email to ticket | | ✓ | ✓ |
| Audit log, data export, SSO | | | ✓ |

Features outside a company's plan are hidden from the menu, and their pages and API endpoints return `403`. Plan limits live in [`app/Enums/Plan.php`](app/Enums/Plan.php).

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+ (8.4 recommended), Laravel 13 |
| Database | PostgreSQL (uses `jsonb`, `generate_series` and `FILTER` aggregates) |
| Auth | Session auth, Laravel Sanctum (API tokens), Laravel Socialite (Google, Facebook), Telegram Login Widget |
| Frontend | Blade, Tabler 1.x, Tabler Icons, ApexCharts, Vite |
| Fonts | Inter, Kantumruy Pro (Khmer) |
| Tests | Pest |

---

## Getting started

### Requirements

- PHP 8.3+ with the `pdo_pgsql`, `zip`, `openssl` and `fileinfo` extensions
- Composer
- Node.js 20+ and npm
- PostgreSQL 14+

### Install

```bash
git clone <repository-url> helpdesk
cd helpdesk

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create two databases, one for the app and one for the tests:

```bash
createdb it_help_desk
createdb it_help_desk_testing
```

Set the connection in `.env`. Adjust the host, port and user to your server; DBngin's PostgreSQL uses port `5433`.

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=it_help_desk
DB_USERNAME=postgres
DB_PASSWORD=
```

Run the migrations with demo data, then start everything:

```bash
php artisan migrate --seed
composer run dev
```

`composer run dev` starts the web server, the Vite dev server and a queue worker. The scheduler is not included; in a second terminal run:

```bash
php artisan schedule:work
```

The scheduler runs `sla:check` every minute. Without it, SLA breaches are not flagged or escalated.

Open <http://localhost:8000>.

---

## Demo accounts

The seeder creates three companies. Every password is `password`.

| Email | Company | Plan | Role |
|---|---|---|---|
| `platform@example.com` | Kdigitized Solutions | Enterprise | Administrator + **platform admin** |
| `admin@example.com` | Acme Corporation | Enterprise | Administrator |
| `supervisor@example.com` | Acme Corporation | Enterprise | Supervisor |
| `agent@example.com` | Acme Corporation | Enterprise | Support Agent |
| `test@example.com` | Acme Corporation | Enterprise | Requester |
| `cafe@example.com` | Sunrise Café | Free | Administrator |
| `staff@example.com` | Sunrise Café | Free | Support Agent |

Reset the demo data at any time with `php artisan migrate:fresh --seed`.

---

## Configuration

### Social login

A provider's button only appears on the login page once its credentials are set.

| Provider | `.env` keys | Callback URL to register with the provider |
|---|---|---|
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | `https://your-domain/auth/google/callback` |
| Facebook | `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET` | `https://your-domain/auth/facebook/callback` |
| Telegram | `TELEGRAM_BOT_USERNAME`, `TELEGRAM_BOT_TOKEN` | Create a bot with **@BotFather**, then `/setdomain` |

- Telegram's login popup only works on the domain registered with BotFather, so use a real domain or a tunnel, not plain `localhost`.
- New social users need a company to join: either the join link they arrived from, or a company that accepts their verified email domain.

### Help desk settings

[`config/helpdesk.php`](config/helpdesk.php) holds:

- the copyright holder shown in the footer
- the default role for new users
- the public email domains that can never be used for auto-join
- the SLA *at risk* threshold
- the available languages
- the attachment rules: disk, file count, size and allowed types

| `.env` key | Default | Purpose |
|---|---|---|
| `HELPDESK_DEFAULT_ROLE` | `requester` | Role given to people who sign up themselves |
| `HELPDESK_ATTACHMENTS_DISK` | `local` | Private disk for attachments |

### Mail and queues

Notifications are queued and sent by email and in-app. Configure `MAIL_*` for a real mailer; locally the `log` mailer writes emails to `storage/logs`. `QUEUE_CONNECTION=database` works out of the box.

---

## Running in production

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Keep two processes running:

```bash
php artisan queue:work --tries=3       # notifications
* * * * * php artisan schedule:run     # cron entry: SLA checks every minute
```

- **HTTPS:** serve over HTTPS. Browsers only allow the microphone for dictation and voice notes on secure origins.
- **Attachments:** files live on the private `local` disk (`storage/app/private/attachments`). Include that directory in backups, or point `HELPDESK_ATTACHMENTS_DISK` at a private S3 bucket.
- **Platform admin:** make your own account the platform admin:

  ```bash
  php artisan tinker --execute 'App\Models\User::withoutGlobalScopes()->where("email", "you@company.com")->update(["is_platform_admin" => true]);'
  ```

---

## Architecture

### Multi-tenancy

One database is shared by all companies; every company-owned table has an `organization_id`.

- **Scoping:** models that use the [`BelongsToOrganization`](app/Models/Concerns/BelongsToOrganization.php) trait are automatically limited to the current company and stamped with it on create.
- **Current company:** [`App\Support\Tenancy`](app/Support/Tenancy.php) holds it. It is a scoped binding, reset for every request and every queued job.
- **Middleware:** [`IdentifyOrganization`](app/Http/Middleware/IdentifyOrganization.php) sets the company from the signed-in user. It runs **before route model binding**, so another company's records resolve to `404`.
- **Validation:** rules use `app(Tenancy::class)->exists('categories')` / `->unique(...)` instead of `Rule::exists`, so IDs from other companies are rejected.
- **Cross-company jobs:** jobs such as `sla:check` run each ticket inside its own company with `Tenancy::runAs()`.
- **Email addresses:** each email belongs to exactly one company.

### Domain logic

| Area | Location |
|---|---|
| Ticket actions (create, update, reply, escalate, attachments) | `app/Actions/Tickets` |
| Company sign-up | `app/Actions/Organizations/CreateOrganization.php` |
| Social sign-in | `app/Actions/Auth`, `app/Services/Auth` |
| SLA calendars, pausing, breach monitor | `app/Services/Sla` |
| Workflow engine | `app/Services/Workflows/WorkflowEngine.php` |
| Reports | `app/Services/Reports/HelpDeskReport.php` |
| Ticket changes (keeps SLA, activity log and notifications in sync) | `app/Services/TicketUpdater.php` |
| Permissions, plans, templates and statuses | `app/Enums` |

Changes made by a workflow never trigger other workflows, so rules cannot loop.

### Permissions

Each permission is a Laravel gate named after the permission, for example `tickets.view_all`, `reports.view` or `organization.manage`. A gate passes only when the user's role grants the permission **and** the company's plan includes the feature it needs. Routes use `can:` middleware, and views use `@can`.

---

## REST API

- **Base URL:** `/api/v1`
- **Auth:** Bearer token from Sanctum. Users can create tokens on the *API tokens* page, or exchange credentials for one.
- **Plan:** the API needs a plan with the API feature (Business or Enterprise).
- **Language:** responses use the user's language, or the `Accept-Language` header.

```bash
curl -X POST https://your-domain/api/v1/auth/token \
  -H "Accept: application/json" \
  -d email=agent@example.com -d password=password -d device_name=cli
```

| Method | Endpoint | Notes |
|---|---|---|
| `POST` | `/auth/token` | Get a token |
| `DELETE` | `/auth/token` | Revoke the current token |
| `GET` | `/me` | Current user, role and effective permissions |
| `GET` | `/tickets` | Filters: `search`, `status`, `priority`, `category`, `assignee=me\|unassigned`, `sla=at_risk\|breached\|escalated`, `per_page` |
| `POST` | `/tickets` | Create. Accepts multipart `attachments[]` and `voice_note` |
| `GET` | `/tickets/{id}` | Includes SLA, attachments and escalations |
| `PATCH` | `/tickets/{id}` | `status`, `priority`, `category_id`, `assignee_id`. Fields you lack permission for are rejected |
| `DELETE` | `/tickets/{id}` | Requires `tickets.delete` |
| `GET` / `POST` | `/tickets/{id}/comments` | Internal notes only for allowed users |
| `POST` | `/tickets/{id}/escalations` | `reason`, optional `escalate_to_id` |
| `GET` | `/tickets/{id}/activities` | Staff only |
| `GET` | `/attachments/{id}` | Download a file |
| `GET` | `/categories` | |
| `GET` | `/reports/summary` | `from`, `to` (`Y-m-d`). Requires `reports.view` |

---

## Localization

- **Languages:** English and Khmer (`km`). People switch from the header; the choice is saved to their account.
- **Fallback order:** saved language → session → company default → browser language.
- **Files:** interface strings are in [`lang/km.json`](lang/km.json), keyed by the English text. Framework messages (validation, auth, pagination) are in `lang/km/*.php`.
- **Activity log:** entries are stored as English templates and translated when displayed, so each viewer reads them in their own language.
- **Khmer font:** Kantumruy Pro is bundled with only its Khmer subset, so it downloads only when Khmer text is on the page.

Add a new language in three steps:

1. Add it to `locales` in `config/helpdesk.php`.
2. Add `lang/<code>.json`.
3. Add `lang/<code>/*.php`.

A test fails if any `__()` string in the views or app code has no Khmer translation.

---

## Testing

Tests run against the PostgreSQL database `it_help_desk_testing` (see `phpunit.xml`). Every test runs inside a fresh company on the Enterprise plan.

```bash
php artisan test --compact                               # full suite
php artisan test --compact tests/Feature/SlaTest.php     # one file
php artisan test --compact --filter="tenant"             # by name
```

Format PHP code with Pint before committing:

```bash
vendor/bin/pint
```

---

## Roadmap

- [x] Tickets, SLA, escalation, workflows, reports, API
- [x] Attachments, voice notes, dictation
- [x] English and Khmer
- [x] Social login (Google, Facebook, Telegram)
- [x] Multi-tenancy, plans, platform console
- [ ] Departments and agent teams (team queues, routing, team reports)
- [ ] Audit log and full data export
- [ ] Email to ticket and knowledge base
- [ ] Enterprise SSO (Microsoft Entra ID, SAML 2.0)
- [ ] Online billing for plans

---

© [Kdigitized Solutions](https://kdigitized.com). All rights reserved.
