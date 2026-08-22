# Tasks: Application History, Notifications & Audit Log

**Input**: Design documents from `specs/98888-application-history-notifications-audit-log/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Include focused Laravel Feature tests and Citizen route-render/build checks because the feature touches authorization, workflow traceability, and user-facing UI.

**Organization**: Tasks are grouped by user story to enable independent implementation, testing, and PR slicing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to
- Each task includes exact file paths

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm existing foundations and create shared primitives only where needed.

- [x] T001 Review existing timeline/audit/notification schema and document any needed schema gap in `specs/98888-application-history-notifications-audit-log/research.md`
- [x] T002 [P] Add reusable application status label/message mapping in `app/Support/Application/ApplicationStatusPresenter.php`
- [x] T003 [P] Add matching Citizen status label/message mapping in `resources/js/citizen/utils/applicationStatus.js`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared event creation used by notification and audit stories.

**CRITICAL**: No workflow notification/audit story should begin until this phase is complete.

- [x] T004 Create application workflow audit logger in `app/Support/Application/ApplicationActivityLogger.php`
- [x] T005 Create application notification builder in `app/Notifications/ApplicationStatusNotification.php`
- [x] T006 Create workflow event dispatcher/service in `app/Support/Application/ApplicationWorkflowNotifier.php`
- [x] T007 Integrate workflow event dispatcher into `app/Actions/Application/CreateApplicationAction.php` without changing application statuses
- [x] T008 Integrate workflow event dispatcher into `app/Actions/Application/TransitionsApplication.php` after successful status history creation
- [x] T009 Integrate audit logging for assignment/claim/result-document actions in `app/Http/Controllers/Admin/Applications/ApplicationController.php`
- [ ] T010 [P] Add workflow event unit coverage in `tests/Feature/ApplicationWorkflowTraceabilityTest.php`

**Checkpoint**: Application workflow can create status history, safe audit records, and in-app notification records from shared code.

---

## Phase 3: User Story 1 - Citizen Tracks Application Progress (Priority: P1) MVP

**Goal**: Citizens can understand application history and required action from application detail.

**Independent Test**: Submit an application, perform status changes, view the citizen application detail, and confirm chronological Vietnamese timeline entries.

### Tests for User Story 1

- [x] T011 [P] [US1] Add API test for Vietnamese timeline labels/descriptions in `tests/Feature/Api/V1/ApplicationTimelineTest.php`
- [x] T012 [P] [US1] Add ownership denial test for another citizen's timeline in `tests/Feature/Api/V1/ApplicationTimelineTest.php`
- [x] T013 [P] [US1] Add Citizen SPA route render test for application detail timeline in `tests/Feature/CitizenSpaTest.php`

### Implementation for User Story 1

- [x] T014 [US1] Add timeline label/description fields to `app/Http/Resources/Api/V1/ApplicationResource.php`
- [x] T015 [US1] Polish timeline rendering and empty state in `resources/js/citizen/pages/MyApplicationDetailPage.jsx`
- [x] T016 [US1] Ensure supplement/result timeline text uses Vietnamese helper labels in `resources/js/citizen/utils/applicationStatus.js`

**Checkpoint**: Citizen timeline is fully functional and testable without notification UI.

---

## Phase 4: User Story 2 - Citizen Receives Application Notifications (Priority: P1)

**Goal**: Citizens receive, view, and mark in-app notifications for application events.

**Independent Test**: Trigger supplement, approval, rejection, and result-document events, then confirm unread count/list/read state for the affected citizen only.

### Tests for User Story 2

- [x] T017 [P] [US2] Add notification API list/read tests in `tests/Feature/Api/V1/CitizenNotificationTest.php`
- [x] T018 [P] [US2] Add notification authorization tests in `tests/Feature/Api/V1/CitizenNotificationTest.php`
- [x] T019 [P] [US2] Add workflow notification creation tests in `tests/Feature/ApplicationWorkflowNotificationTest.php`
- [x] T020 [P] [US2] Add Citizen header notification render test in `tests/Feature/CitizenSpaTest.php`

### Implementation for User Story 2

- [x] T021 [US2] Create notification resource in `app/Http/Resources/Api/V1/NotificationResource.php`
- [x] T022 [US2] Create notification filter request in `app/Http/Requests/Api/V1/Notification/IndexNotificationRequest.php`
- [x] T023 [US2] Create Citizen notification controller in `app/Http/Controllers/Api/V1/NotificationController.php`
- [x] T024 [US2] Register Citizen notification routes in `routes/api.php`
- [x] T025 [US2] Add Citizen notification API client in `resources/js/citizen/api/notifications.js`
- [x] T026 [US2] Add notification dropdown/list component in `resources/js/citizen/components/NotificationMenu.jsx`
- [x] T027 [US2] Integrate notification unread badge/menu into `resources/js/citizen/components/Header.jsx`
- [x] T028 [US2] Link notifications to application detail routes in `resources/js/citizen/App.jsx`

**Checkpoint**: Citizen notifications are independently usable from the Citizen area.

---

## Phase 5: User Story 3 - Admin Reviews Activity Logs (Priority: P2)

**Goal**: Authorized internal users can browse and search audit activity.

**Independent Test**: Perform audited actions, open `/admin/activity-logs`, filter by actor/action/date/keyword, and confirm unauthorized users are denied.

### Tests for User Story 3

- [x] T029 [P] [US3] Add admin activity log authorization tests in `tests/Feature/Admin/ActivityLogTest.php`
- [x] T030 [P] [US3] Add admin activity log filter/search tests in `tests/Feature/Admin/ActivityLogTest.php`
- [x] T031 [P] [US3] Add application workflow audit tests in `tests/Feature/Admin/ApplicationWorkflowAuditTest.php`

### Implementation for User Story 3

- [x] T032 [US3] Add activity log query scopes to `app/Models/ActivityLog.php`
- [x] T033 [US3] Create admin activity log index request in `app/Http/Requests/Admin/ActivityLogs/IndexActivityLogRequest.php`
- [x] T034 [US3] Create admin activity log controller in `app/Http/Controllers/Admin/ActivityLogs/ActivityLogController.php`
- [x] T035 [US3] Register `/admin/activity-logs` route in `routes/web.php`
- [x] T036 [US3] Create admin activity log Blade view in `resources/views/admin/activity-logs/index.blade.php`
- [x] T037 [US3] Add navigation entry for activity logs in `resources/views/admin/layouts/app.blade.php`
- [x] T038 [US3] Ensure workflow audit metadata avoids sensitive fields in `app/Support/Application/ApplicationActivityLogger.php`

**Checkpoint**: Admin audit log search is independently usable from the Admin area.

---

## Phase 6: User Story 4 - Optional Email Notification Delivery (Priority: P3)

**Goal**: Prepare email notifications for high-importance citizen events if time permits.

**Independent Test**: Enable email notifications for a citizen, trigger a major status change, and confirm an email-ready notification is queued/prepared without blocking workflow completion.

### Tests for User Story 4

- [x] T039 [P] [US4] Add optional mail channel test in `tests/Feature/ApplicationWorkflowEmailNotificationTest.php`

### Implementation for User Story 4

- [x] T040 [US4] Add optional mail representation to `app/Notifications/ApplicationStatusNotification.php`
- [x] T041 [US4] Respect `email_notifications_enabled` in `app/Notifications/ApplicationStatusNotification.php`
- [x] T042 [US4] Document mail environment expectations in `specs/98888-application-history-notifications-audit-log/quickstart.md`

**Checkpoint**: Email notification support is present only if it does not delay required F06 scope.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final validation, cleanup, and Redmine/PR readiness.

- [x] T043 [P] Update F06 contracts if endpoint or UI behavior changed in `specs/98888-application-history-notifications-audit-log/contracts/`
- [x] T044 [P] Review Vietnamese copy consistency in `resources/js/citizen` and `resources/views/admin/activity-logs/index.blade.php`
- [x] T045 Run `php artisan test --env=testing --filter=ApplicationTimelineTest`
- [x] T046 Run `php artisan test --env=testing --filter=CitizenNotificationTest`
- [x] T047 Run `php artisan test --env=testing --filter=ActivityLogTest`
- [ ] T048 Run full validation: `php artisan test --env=testing`, `composer run lint`, `npm run lint`, and `npm run build`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies.
- **Foundational (Phase 2)**: Depends on Phase 1 and blocks workflow notification/audit work.
- **US1 Timeline (Phase 3)**: Can start after Phase 1 because timeline data already exists.
- **US2 Notifications (Phase 4)**: Depends on Phase 2.
- **US3 Admin Audit Log (Phase 5)**: Depends on Phase 2.
- **US4 Email (Phase 6)**: Depends on US2 and is optional.
- **Polish (Phase 7)**: Depends on selected user stories being complete.

### User Story Dependencies

- **US1 (P1)**: Independent MVP; no dependency on notifications.
- **US2 (P1)**: Requires shared workflow dispatcher/notifier from Phase 2.
- **US3 (P2)**: Requires shared audit logger from Phase 2.
- **US4 (P3)**: Optional extension of US2.

### Parallel Opportunities

- T002 and T003 can run in parallel.
- T004, T005, and T010 can run in parallel after setup review.
- US1 tests T011-T013 can run in parallel.
- US2 tests T017-T020 can run in parallel.
- US3 tests T029-T031 can run in parallel.
- US2 UI tasks T025-T027 can run in parallel with backend tasks T021-T024 after contracts are stable.
- US3 Blade view T036 can run in parallel with request/controller tasks T033-T034 after route shape is agreed.

---

## Parallel Example: User Story 2

```text
Task: "Add notification API list/read tests in tests/Feature/Api/V1/CitizenNotificationTest.php"
Task: "Add workflow notification creation tests in tests/Feature/ApplicationWorkflowNotificationTest.php"
Task: "Add Citizen notification API client in resources/js/citizen/api/notifications.js"
Task: "Add notification dropdown/list component in resources/js/citizen/components/NotificationMenu.jsx"
```

---

## Implementation Strategy

### MVP First

1. Complete Phase 1.
2. Complete US1 timeline polish and tests.
3. Stop and validate Citizen application detail before expanding scope.

### Required F06 Scope

1. Complete Phase 2 shared event creation.
2. Complete US2 Citizen notifications.
3. Complete US3 Admin audit log search.
4. Validate all required authorization boundaries.

### Optional Scope

1. Add US4 email only after US1, US2, and US3 are stable.
2. Skip US4 if mail setup slows down the required sprint scope.

## Notes

- No Figma mockups are required for this task list; Citizen UI should follow existing React/Tailwind components and Admin UI should follow existing Blade admin cards/forms/tables.
- Keep each Redmine task/PR scoped to one checkpoint where possible.
- Do not commit `.env.testing` or local Redmine scratch files.
