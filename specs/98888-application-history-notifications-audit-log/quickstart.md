# Quickstart: Application History, Notifications & Audit Log

## Prerequisites

- Local `.env.testing` points to a writable PostgreSQL database.
- Dependencies are installed with Composer and npm.
- Existing F04/F05 application submission and processing workflow is available.

## Setup

```powershell
php artisan migrate:fresh --seed --env=testing
```

## Validation Scenarios

### 1. Citizen Timeline

1. Register or use an existing citizen account.
2. Create an application.
3. As internal user, move the application through processing and supplement request.
4. Open the citizen application detail.
5. Confirm timeline entries are ordered and show Vietnamese labels, actor names where available, and notes.

Expected result: the citizen sees only their own application's history and cannot access another citizen's timeline.

### 2. Citizen Notifications

1. Trigger supplement requested, approved, rejected, and result document available events.
2. Log in as the affected citizen.
3. Open the notification list or header notification menu.
4. Mark one notification as read, then mark all as read.

Expected result: unread count updates correctly and all notification messages are Vietnamese.

### 3. Admin Activity Log

1. Perform actions that should be audited: login, service update, application assignment, supplement request, approval, rejection.
2. Log in as an authorized internal user.
3. Open `/admin/activity-logs`.
4. Filter by action, actor, date range, and keyword.

Expected result: matching logs appear with actor, action, subject, timestamp, and safe metadata summary.

### 4. Authorization Boundaries

1. Attempt to access another citizen's notifications or timeline.
2. Attempt to access the admin activity log as a citizen.
3. Attempt to read or mark another user's notification.

Expected result: all unauthorized access is denied.

## Automated Checks

```powershell
php artisan test --env=testing
composer run lint
npm run lint
npm run build
```

If local npm is broken, validate the Vite build directly with the local Vite binary and document the environment issue in the PR.
