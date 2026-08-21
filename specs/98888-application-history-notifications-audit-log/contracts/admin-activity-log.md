# Contract: Admin Activity Log

## Audience

Authorized Staff, Manager, and Super Admin users in the Admin area.

## Authorization

- Requires authenticated internal user access.
- Citizen users must not access this screen.
- If later role restrictions are needed, Super Admin and Manager should be the first eligible roles for broad audit review.

## Admin Screen

`GET /admin/activity-logs`

## Filters

- `q`: keyword matching description, action, actor name/email, or known subject snapshot.
- `actor_id`: actor user id.
- `action`: exact action key.
- `subject_type`: subject category where available.
- `date_from`: start date.
- `date_to`: end date.
- `page`: page number.

## List Row

Each row should show:

- Timestamp.
- Actor name, email, and role when available.
- Action key or readable action label.
- Subject summary when available.
- Description.
- Request context summary such as IP address.

## Detail Expansion

The screen may show safe metadata details inline or through a detail view. Sensitive values, uploaded document contents, tokens, and secrets must not be displayed.

## Empty and Error States

- No matching logs shows a Vietnamese empty state.
- Invalid filters are handled with validation feedback.
- Unauthorized users are redirected or denied according to existing Admin behavior.
