# Feature Specification: Application History, Notifications & Audit Log

**Feature Branch**: `98888-application-history-notifications-audit-log`

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "Feature #98888 - F06 Application History, Notifications & Audit Log"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Citizen Tracks Application Progress (Priority: P1)

A citizen needs to see a clear timeline for each submitted application so they can understand what happened, when it happened, and whether they need to take action.

**Why this priority**: Application history is the core user-facing value of this feature and reduces uncertainty during public-service processing.

**Independent Test**: Submit an application, perform at least two status changes, then view the citizen application detail and confirm the timeline shows the expected events in chronological order with user-friendly Vietnamese text.

**Acceptance Scenarios**:

1. **Given** a citizen owns an application with multiple status changes, **When** the citizen opens the application detail, **Then** they see a timeline showing each status change, date/time, responsible actor where appropriate, and notes when available.
2. **Given** a citizen owns an application that requires supplement, **When** the citizen views the timeline, **Then** the supplement request appears clearly with the reason or instruction left by staff.
3. **Given** a citizen tries to view another citizen's application history, **When** the request is made, **Then** access is denied.

---

### User Story 2 - Citizen Receives Application Notifications (Priority: P1)

A citizen needs to receive notifications when their application changes status or requires action so they do not need to repeatedly check the site manually.

**Why this priority**: Notifications make the application workflow actionable and prevent missed supplement requests or result updates.

**Independent Test**: Trigger application assignment, supplement request, approval, and rejection scenarios, then confirm the affected citizen receives readable notifications and can distinguish unread from read notifications.

**Acceptance Scenarios**:

1. **Given** an application status changes, **When** the change is completed, **Then** the citizen receives a notification describing the new status and application code.
2. **Given** a citizen has unread notifications, **When** they open the citizen area, **Then** they can see the unread count and notification list.
3. **Given** a citizen opens or marks notifications as read, **When** the read action succeeds, **Then** those notifications no longer count as unread.

---

### User Story 3 - Admin Reviews Activity Logs (Priority: P2)

An authorized internal user needs to search and review important system activity so operational decisions and suspicious actions can be traced later.

**Why this priority**: Auditability is required for accountability, debugging, and public-service operational review.

**Independent Test**: Perform representative actions such as login, service changes, assignment, approval, rejection, and supplement request, then search the admin activity log by actor, action, subject, and date range.

**Acceptance Scenarios**:

1. **Given** important actions have occurred, **When** an authorized internal user opens the activity log, **Then** they see a paginated list with actor, action, subject, timestamp, and readable description.
2. **Given** many activity records exist, **When** an authorized internal user filters by actor, action type, date range, or keyword, **Then** only matching records are shown.
3. **Given** a citizen or unauthorized user attempts to view activity logs, **When** the request is made, **Then** access is denied.

---

### User Story 4 - Optional Email Notification Delivery (Priority: P3)

A citizen may receive email notifications for important application changes when email notification delivery is available within the sprint.

**Why this priority**: Email improves reach but is secondary to in-app notifications because the sprint already guarantees authenticated citizen access.

**Independent Test**: Enable email notifications for a citizen, trigger a major status change, and confirm an email-ready notification is produced without blocking the workflow if delivery is unavailable.

**Acceptance Scenarios**:

1. **Given** a citizen has email notifications enabled, **When** their application is approved, rejected, or requires supplement, **Then** an email notification is prepared with a clear Vietnamese subject and message.
2. **Given** email delivery is unavailable, **When** a status change occurs, **Then** the application workflow still completes and the in-app notification remains available.

---

### Edge Cases

- If an application has no history beyond creation, the timeline shows the initial submission state instead of an empty or broken view.
- If an actor account is deactivated, deleted, or unavailable, historical records still show a stable fallback identity.
- If notifications are generated more than once for the same completed workflow action, the citizen should not receive confusing duplicate messages.
- If metadata for an activity record is partial, the admin log still displays the action safely without exposing raw or sensitive internal data unnecessarily.
- If a citizen has many notifications, the notification list remains paginated or otherwise bounded.
- If a user lacks permission, history, notification, and audit-log data must not be exposed through direct URLs or crafted requests.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST preserve a chronological history of application status changes, including the previous status, new status, timestamp, responsible actor when known, and an optional note.
- **FR-002**: Citizens MUST be able to view a readable timeline for their own applications.
- **FR-003**: Citizens MUST NOT be able to view timelines, notifications, documents, or activity information belonging to other citizens.
- **FR-004**: The system MUST create citizen notifications for important application events, including submission received, supplement requested, supplement received, approved, rejected, and result document available.
- **FR-005**: Citizens MUST be able to view their notifications, identify unread notifications, and mark notifications as read.
- **FR-006**: Notification messages MUST be written in Vietnamese and include enough context for the citizen to identify the affected application.
- **FR-007**: The system MUST record audit activity for important security and workflow events, including login, service creation/update, application assignment, status transition, supplement request, approval, rejection, and result document upload.
- **FR-008**: Authorized internal users MUST be able to browse and search audit activity by actor, action type, subject, keyword, and date range.
- **FR-009**: Audit-log access MUST be denied to citizens and unauthorized internal users.
- **FR-010**: Activity records MUST remain understandable even if the related actor or subject later changes or is unavailable.
- **FR-011**: Workflow actions MUST remain successful even if optional notification delivery cannot be completed immediately.
- **FR-012**: Email notifications SHOULD be available for high-importance citizen events if time permits, without replacing in-app notifications.
- **FR-013**: The system MUST avoid exposing sensitive document contents or secret values in notification text or activity-log descriptions.
- **FR-014**: Timeline, notification, and audit-log views MUST show newest and historical records in a consistent, predictable order.

### Key Entities *(include if feature involves data)*

- **Application Status History**: A historical record of an application's status transition, including from-status, to-status, actor, note, and time of change.
- **Citizen Notification**: A message for a citizen about an application event, including read state, creation time, and contextual message data.
- **Activity Log**: An audit record for important security, management, or workflow actions, including actor, action, subject, description, metadata, request context, and time.
- **Application**: The public-service submission whose lifecycle creates timeline events, citizen notifications, and audit activity.
- **User**: A citizen or internal actor who may receive notifications, cause workflow events, or appear in audit history.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A citizen can understand the current and previous application statuses from the detail page in under 30 seconds without contacting staff.
- **SC-002**: 100% of supplement request, approval, and rejection events create an in-app citizen notification during normal workflow execution.
- **SC-003**: Authorized internal users can locate a known audit event by actor, action type, or date range in under 1 minute.
- **SC-004**: Unauthorized users are denied access to another user's history, notifications, and audit records in all tested direct-access scenarios.
- **SC-005**: Notification and audit failures do not prevent the underlying application workflow action from completing successfully in tested non-critical delivery failure scenarios.
- **SC-006**: Vietnamese user-facing messages are used for citizen timeline and notification content in all primary scenarios.

## Assumptions

- Existing application submission and processing workflows are available and remain the source of truth for status changes.
- Existing authentication and role boundaries are reused: citizens use the citizen area, while staff, managers, and super admins use the admin area.
- In-app database notifications are required for this feature; email notifications are optional if delivery setup and time allow.
- Audit logs are intended for operational traceability and review, not for exporting legal compliance reports in this feature.
- Existing application history and activity records should be reused where they already capture the required facts.
- Notification and activity-log retention follows the project's default data retention unless a later policy defines stricter requirements.
