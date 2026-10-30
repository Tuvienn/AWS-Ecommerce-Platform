#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${project_root}"

if ! command -v composer >/dev/null 2>&1; then
    echo "Chưa có Composer trên host deploy." >&2
    exit 1
fi

if [[ "${APP_ENV:-production}" != "production" ]]; then
    echo "Deploy script yêu cầu APP_ENV=production." >&2
    exit 1
fi

echo "Cài dependency production..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "Chạy application readiness..."
bash deploy/aws/application_readiness.sh

if [[ "${RUN_MIGRATIONS:-false}" == "true" ]]; then
    echo "Chạy database migrations..."
    php database/scripts/migrate.php up
else
    echo "Bỏ qua database migration (đặt RUN_MIGRATIONS=true nếu đã duyệt)."
fi

if [[ "${MIGRATE_STORAGE:-false}" == "true" ]]; then
    echo "Chạy runtime storage migration..."
    php database/scripts/migrate_runtime_uploads.php --apply
else
    echo "Bỏ qua runtime storage migration (đặt MIGRATE_STORAGE=true nếu đã duyệt)."
fi

echo "Application deploy preparation PASS."
