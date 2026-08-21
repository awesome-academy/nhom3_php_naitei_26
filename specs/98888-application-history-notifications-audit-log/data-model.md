# Data Model: Application History, Notifications & Audit Log

## Application

**Purpose**: The public-service submission whose lifecycle is traced by this feature.

**Relevant fields**:

- `id`
- `application_code`
- `citizen_id`
- `service_type_id`
- `assigned_staff_id`
- `status`
- `submitted_at`
- `processing_started_at`
- `completed_at`
- `result_note`
- `rejection_reason`

**Relationships**:

- Belongs to Citizen user.
- Belongs to Service Type.
- Has many Application Status Histories.
- Has many Application Documents.
- May have one assigned Staff user.

**Rules**:

- Timeline and notifications must reflect the existing lifecycle statuses.
- Citizen-visible data must be scoped to the owning citizen.

## Application Status History

**Purpose**: Append-only record of an application lifecycle transition.

**Fields**:

- `id`
- `application_id`
- `from_status`
- `to_status`
- `changed_by`
- `note`
- `created_at`

**Relationships**:

- Belongs to Application.
- Belongs to User through `changed_by`, including historical/deleted actors where possible.

**Validation and rules**:

- `to_status` is required.
- `from_status` may be empty only for initial submission.
- `changed_by` should identify the actor who caused the transition.
- Records are displayed chronologically by `created_at` then `id`.
- Records should not be edited by normal workflow screens.

## Citizen Notification

**Purpose**: A citizen-facing message about an important application event.

**Fields**:

- `id`
- `type`
- `notifiable_type`
- `notifiable_id`
- `data`
- `read_at`
- `created_at`
- `updated_at`

**Message data shape**:

- `application_id`
- `application_code`
- `event`
- `title`
- `message`
- `status`
- `url`
- `occurred_at`

**Relationships**:

- Belongs to a notifiable user.
- References an Application by id in message data.

**Validation and rules**:

- Notifications for F06 are created for the owning citizen only.
- Messages must be Vietnamese.
- Sensitive document contents and secrets must not be embedded in `data`.
- Unread count is based on empty `read_at`.
- Read actions can only update notifications owned by the current citizen.

## Activity Log

**Purpose**: Operational audit trail for security, management, and application workflow actions.

**Fields**:

- `id`
- `actor_id`
- `action`
- `subject_type`
- `subject_id`
- `description`
- `metadata`
- `ip_address`
- `user_agent`
- `created_at`

**Metadata shape**:

- `actor`: stable actor snapshot when available.
- `subject`: stable subject snapshot when available.
- `application`: application snapshot for application workflow actions.
- `before`: changed values before the action when useful and non-sensitive.
- `after`: changed values after the action when useful and non-sensitive.
- `context`: extra safe context such as assignment target, status, or reason summary.

**Relationships**:

- Belongs to User through `actor_id`, nullable for system/unknown actor.
- Morphs to subject where applicable.

**Validation and rules**:

- Admin audit list must support filtering by actor, action, date range, subject, and keyword.
- Logs should be readable even when actor or subject is later deleted.
- Logs must not include raw uploaded document contents, passwords, tokens, or secrets.

## User

**Purpose**: Actor, citizen notification recipient, or internal audit reviewer.

**Relevant fields**:

- `id`
- `name`
- `email`
- `role`
- `is_active`
- `email_notifications_enabled`
- `deleted_at`

**Rules**:

- Citizens can read only their own notifications and application timeline.
- Staff, managers, and super admins may access admin audit logs only when allowed by role policy.
- Email notifications use `email_notifications_enabled` only if optional email scope is implemented.
