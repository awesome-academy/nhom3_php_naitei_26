# Contract: Citizen Application Timeline

## Audience

Authenticated Citizen users viewing their own application details.

## Authorization

- A citizen can only view timeline data for applications they own.
- Internal application details remain under Admin screens and are not exposed through this contract.

## Timeline Data In Application Detail

`GET /api/v1/applications/{application}`

The existing application detail response includes a `timeline` collection when the requester is allowed to see protected application details.

**Timeline item shape**:

```json
{
  "from_status": "received",
  "to_status": "processing",
  "changed_by_name": "Nguyen Van A",
  "note": "Bắt đầu xử lý hồ sơ.",
  "created_at": "2026-08-21T10:00:00Z",
  "label": "Đang xử lý",
  "description": "Hồ sơ đã được chuyển sang trạng thái đang xử lý."
}
```

## Display Expectations

- Timeline is ordered from oldest to newest.
- The current or latest status is visually clear.
- Supplement notes, rejection reasons, and result availability are visible when relevant.
- Empty history falls back to the initial submission event.
- User-facing labels and descriptions are Vietnamese.

## Error Cases

- Unauthenticated request returns `401`.
- Request for another citizen's application returns `403` or `404`.
- Missing application returns `404`.
