# Weekly Reports

A Laravel web app for staff weekly report submission and admin review.

## Overview

Staff users submit one weekly progress report. Admin users can view all reports, filter by staff details, inspect full report content, and mark submitted reports as completed.

## Rules

- Staff can submit one report per ISO week, Monday through Sunday.
- The backend allows submissions only on Thursday, Friday, and Saturday.
- The form remains editable on every day; disallowed days are rejected by the backend after submit.
- Reports submitted on Thursday or Friday before 8:00 PM are saved as `submitted`.
- Reports submitted from Friday 8:00 PM onward, and all Saturday submissions, are saved as `late`.
- Admins can mark `submitted` reports as `completed`.

## Statuses

The `weekly_reports.status` field uses:

- `submitted`
- `completed`
- `late`

Existing old statuses are migrated as:

- `pending` -> `submitted`
- `accepted` -> `completed`
- `unacceptable` -> `late`

## Roles

- `staff`: can submit reports and view their own submission history.
- `admin`: can access the admin dashboard and manage reports.

Self-registration always creates a `staff` user. Admin users are created through the seeder.

## Default Admins

The admin seeder creates two admin accounts using `.env` values:

```env
ADMIN_NAME="Super Admin"
ADMIN_EMAIL=admin@zeltech.com
ADMIN_PASSWORD=password

ADMIN2_NAME="Admin Two"
ADMIN2_EMAIL=admin2@zeltech.com
ADMIN2_PASSWORD=password
```

Change these values before using the app outside local development.

## Setup

Install dependencies:

```bash
composer install
npm install
```

Create the environment file and app key:

```bash
copy .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

Build frontend assets:

```bash
npm run build
```

Run the app locally:

```bash
php artisan serve
```

## Seed Data

`database/seeders/ReportSeeder.php` creates realistic staff users and weekly reports that match the project rules:

- reports are seeded only on Thursday, Friday, or Saturday;
- one report is created per staff member per week;
- Friday after 8:00 PM and Saturday reports are seeded as `late`;
- reviewed on-time reports are seeded as `completed`;
- unreviewed on-time reports are seeded as `submitted`.

Run only the report seeder with:

```bash
php artisan db:seed --class=ReportSeeder
```

## Testing

Run the test suite:

```bash
php artisan test
```

The tests include coverage for the Thursday-Saturday submission window and Friday 8:00 PM late cutoff.

## Main Routes

- `/dashboard`: staff report form and submission history.
- `/admin`: admin dashboard.
- `/admin/reports/{report}`: full report detail.

