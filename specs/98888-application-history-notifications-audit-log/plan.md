# Implementation Plan: Application History, Notifications & Audit Log

**Branch**: `98888-application-history-notifications-audit-log` | **Date**: 2026-08-21 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/98888-application-history-notifications-audit-log/spec.md`

## Summary

F06 adds traceability around the public-service application lifecycle: citizens can see timeline and notification updates for their own applications, while authorized internal users can search operational audit logs. The implementation should reuse the existing application status history, activity log, and notification storage already present in the project, then add focused read surfaces and event creation at existing workflow boundaries.

## Technical Context

**Language/Version**: PHP ^8.3 project baseline, local PHP 8.5, Laravel 13; JavaScript with React 19 for Citizen SPA and Alpine.js for Admin progressive interactions.

**Primary Dependencies**: Laravel Framework 13, Laravel Sanctum, Laravel Notifications, Eloquent, React, React Router, Vite, Tailwind CSS, Alpine.js.

**Storage**: PostgreSQL/Supabase via Eloquent. Existing tables include `application_status_histories`, `activity_logs`, `notifications`, `applications`, and `users`.

**Testing**: PHPUnit/Laravel Feature tests via `php artisan test`; Pint via `composer run lint`; frontend lint via `npm run lint`; production bundle via `npm run build`.

**Target Platform**: Laravel web application with Citizen React SPA under `/` and Admin Blade SSR under `/admin`.

**Project Type**: Monolithic Laravel web application with versioned REST endpoints for Citizen and server-rendered Admin screens.

**Performance Goals**: Citizen notification and timeline views should feel immediate for normal per-user history sizes; admin audit-log search should return paginated results within normal page-load expectations for sprint-scale data.

**Constraints**: Preserve existing Citizen/Admin separation, keep protected data denied by default, do not introduce repositories or large architectural layers, use transactions for workflow mutations, and avoid exposing document contents or secrets in notification/audit text.

**Scale/Scope**: 1.5-day feature scope covering application timeline, in-app citizen notifications, workflow/security audit logging, and admin audit-log search. Email notification is optional and only included if it does not delay required in-app and audit scope.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Laravel-first backend & simplicity**: PASS. Plan uses controllers, requests, policies, resources, notifications, and focused support/actions already established in the codebase.
- **Feature-driven development**: PASS. Spec and plan exist before tasks/implementation; Redmine #98888 remains the tracking record.
- **Application-centric domain**: PASS. Timeline and notifications are derived from the existing application lifecycle and do not introduce new statuses.
- **Authorization & data protection**: PASS. Citizen surfaces are scoped to owned applications/notifications; admin audit logs require internal authorization.
- **Database integrity & auditability**: PASS. Existing history/audit tables preserve current state separately from history and actor context.
- **Citizen React SPA & Admin Blade SSR**: PASS. Citizen notifications/timeline stay in React SPA and `/api/v1`; admin audit log stays in Blade under `/admin`.
- **Quality & definition of done**: PASS. Plan includes API/Admin tests, authorization tests, lint, and build validation.

## Project Structure

### Documentation (this feature)

```text
specs/98888-application-history-notifications-audit-log/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── citizen-notifications-api.md
│   ├── citizen-application-timeline.md
│   └── admin-activity-log.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Actions/
│   └── Application/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Api/V1/
│   ├── Requests/
│   └── Resources/
│       └── Api/V1/
├── Models/
├── Notifications/
├── Policies/
└── Support/

resources/
├── js/citizen/
│   ├── api/
│   ├── components/
│   └── pages/
└── views/admin/
    └── activity-logs/

routes/
├── api.php
└── web.php

tests/
├── Feature/Api/V1/
└── Feature/Admin/
```

**Structure Decision**: Use the existing Laravel monolith structure. Citizen read/write interactions are added to REST resources and React SPA modules. Internal audit review is added as an Admin Blade screen. Shared event creation should live in focused support classes or existing workflow actions only when it removes duplication.

## Complexity Tracking

No constitution violations are planned.

## Phase 0 Research

See [research.md](research.md). All design questions are resolved with existing project conventions and no unresolved clarifications.

## Phase 1 Design

See [data-model.md](data-model.md), [quickstart.md](quickstart.md), and contracts under [contracts/](contracts/).

## Post-Design Constitution Check

- **Laravel-first backend & simplicity**: PASS. Design avoids new architectural layers and keeps event creation at existing workflow boundaries.
- **Feature-driven development**: PASS. Next step is dependency-ordered `tasks.md`.
- **Application-centric domain**: PASS. F06 reads and extends traceability around existing application statuses only.
- **Authorization & data protection**: PASS. Contracts explicitly require citizen ownership and internal-only audit access.
- **Database integrity & auditability**: PASS. Existing historical records remain append-only, and activity metadata uses snapshots for resilience.
- **Citizen React SPA & Admin Blade SSR**: PASS. Contracts preserve React citizen and Blade admin boundaries.
- **Quality & definition of done**: PASS. Quickstart includes backend feature tests, admin tests, lint, and build.
