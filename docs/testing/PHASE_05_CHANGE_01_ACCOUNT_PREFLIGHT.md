# Phase 5 — Change 1: Account preflight và cost guard

## Trạng thái

```text
PHASE: Phase 5 — AWS Infrastructure
CHANGE: 1
KẾT QUẢ: LOCAL PREPARATION PASS — AWS ACCOUNT PREFLIGHT PENDING
NGÀY KIỂM TRA: 2026-10-28
```

## File thay đổi

### `deploy/aws/preflight.sh`

```text
PURPOSE: Kiểm tra read-only AWS identity và availability zone trước provision.
WHAT CHANGED: Kiểm tra environment bắt buộc, AWS CLI, STS identity và AZ.
WHY: Không tạo resource trước khi xác minh account/region/cidr.
ROLE IN SYSTEM: Account preflight guard.
RISK: Cần AWS CLI/profile ngoài repository để chạy thật.
HOW TO TEST: Export biến môi trường và chạy bash deploy/aws/preflight.sh.
RESULT: Chưa chạy được vì môi trường hiện tại không có AWS CLI.
```

### `deploy/aws/parameters.example.env`, `deploy/aws/README.md`

```text
PURPOSE: Chuẩn hóa parameter không nhạy cảm và hướng dẫn cost guard.
WHAT CHANGED: Thêm placeholder region/project/environment/admin CIDR.
WHY: Tránh hard-code hoặc commit credential.
ROLE IN SYSTEM: Input contract cho provision Phase 5.
RISK: Admin CIDR sai có thể khóa quản trị hoặc mở SSH quá rộng.
HOW TO TEST: Review placeholder; không dùng 0.0.0.0/0 cho SSH.
RESULT: PASS local review.
```

## Blocker

AWS CLI/account scope, Region, cost approval và admin CIDR chưa được cung cấp.
Do đó Change 2–7 chưa được provision và không có AWS cost phát sinh.

