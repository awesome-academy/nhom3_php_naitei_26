# Contract: Citizen Notifications API

## Audience

Authenticated Citizen users.

## Authorization

- Requires an authenticated Citizen session.
- A citizen can only access notifications where they are the recipient.
- Internal users cannot use this Citizen notification surface.

## Endpoints

### List Notifications

`GET /api/v1/notifications`

**Query parameters**:

- `filter`: optional, one of `all`, `unread`, `read`; default `all`.
- `page`: optional page number.

**Success response**:

```json
{
  "success": true,
  "message": "Lấy danh sách thông báo thành công.",
  "data": {
    "unread_count": 2,
    "notifications": [
      {
        "id": "uuid",
        "event": "application.supplement_requested",
        "title": "Cần bổ sung hồ sơ",
        "message": "Hồ sơ HS-20260821-00001 cần bổ sung tài liệu.",
        "application_id": 1,
        "application_code": "HS-20260821-00001",
        "status": "supplement_required",
        "url": "/applications/1",
        "read_at": null,
        "created_at": "2026-08-21T10:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 10,
      "total": 1
    }
  }
}
```

### Mark One Notification As Read

`PATCH /api/v1/notifications/{notification}/read`

**Success response**:

```json
{
  "success": true,
  "message": "Đã đánh dấu thông báo là đã đọc.",
  "data": {
    "id": "uuid",
    "read_at": "2026-08-21T10:05:00Z"
  }
}
```

### Mark All Notifications As Read

`PATCH /api/v1/notifications/read-all`

**Success response**:

```json
{
  "success": true,
  "message": "Đã đánh dấu tất cả thông báo là đã đọc.",
  "data": {
    "unread_count": 0
  }
}
```

## Error Cases

- Unauthenticated request returns `401`.
- Non-citizen request returns `403`.
- Attempting to read another user's notification returns `404` or `403`.
- Invalid filter returns validation error with Vietnamese message.
