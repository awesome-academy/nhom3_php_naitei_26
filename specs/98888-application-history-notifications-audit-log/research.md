# Research: Application History, Notifications & Audit Log

## Decision: Reuse Existing Status History as the Timeline Source

**Rationale**: The project already records application status transitions in `application_status_histories` with previous status, next status, actor, note, and timestamp. This matches the timeline requirements and respects the constitution rule that current state and history stay separate.

**Alternatives considered**:

- Create a new timeline table: rejected because it would duplicate the same lifecycle facts.
- Derive history from current application fields only: rejected because it cannot reconstruct previous steps or actor notes reliably.

## Decision: Use In-App Database Notifications as Required Scope

**Rationale**: The feature backlog lists database notification as required and email notification as optional. The project already has the standard notifications table and users can receive notifications. In-app notifications are reliable for local development and do not depend on external mail setup.

**Alternatives considered**:

- Email-only notifications: rejected because delivery setup can vary across local machines and would not provide an unread list inside the app.
- Browser push notifications: rejected as out of scope for the sprint.

## Decision: Keep Email Notifications Optional

**Rationale**: Email is explicitly "if time" in the backlog. The required value is achieved through in-app notifications and audit logs. If implemented, email delivery must not block workflow actions.

**Alternatives considered**:

- Make email mandatory: rejected because it can slow the feature with infrastructure and environment setup.
- Omit email entirely from design: rejected because the feature asks to preserve it as optional sprint scope.

## Decision: Record Audit Logs at Workflow and Security Boundaries

**Rationale**: Important events should be captured at the place where the system has the actor, subject, request context, and final outcome. Existing department work already has an activity logger pattern that can be mirrored for application workflow and reused in admin search.

**Alternatives considered**:

- Record logs only in controllers: rejected because workflow mutations can be called from multiple surfaces and controllers should stay thin.
- Record every model save automatically: rejected because it creates noisy logs and risks leaking sensitive metadata.

## Decision: Add Read Surfaces Without Changing Application Statuses

**Rationale**: F06 is traceability, not workflow design. It should display and notify about existing statuses: received, processing, supplement required, approved, rejected.

**Alternatives considered**:

- Add notification-specific pseudo statuses: rejected because it would alter the application lifecycle and violate the application-centric domain boundary.

## Decision: Use Existing Schema First; Add Only Minimal Indexes If Needed

**Rationale**: Existing tables already support timeline by application, audit filtering by actor/action/time/subject, and notification ownership. If implementation introduces frequent unread-count/list queries, a small notification index may be justified, but the base feature should avoid unnecessary schema churn.

**Post-pull F06-01 review**: Citizen timeline polish does not require a new migration. Existing `application_status_histories` fields cover status labels, descriptions, actor names, notes, and chronological ordering.

**Alternatives considered**:

- Add separate notification preference, delivery, and template tables now: rejected as too broad for the required MVP.
- Store notification read state outside the notifications table: rejected because the current table already models it.

## Decision: Vietnamese User-Facing Messages

**Rationale**: The project already moved auth-facing messages to Vietnamese, and F06 is citizen-facing. Timeline and notification text should be consistent with that experience.

**Alternatives considered**:

- English internal/action keys only: rejected for citizen UI because it would not meet the user-facing acceptance criteria.
