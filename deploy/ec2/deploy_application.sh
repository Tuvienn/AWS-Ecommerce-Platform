#!/usr/bin/env bash
# Deploy source đã có sẵn tại EC2. Script không clone/pull Git, không đổi Nginx
# và không chạm vào website PHP cũ. Dùng --dry-run để xem trước.

set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DRY_RUN=false
SEED=false
IMPORT_CATALOG=false
MIGRATE=false

usage() {
    cat <<'USAGE'
Sử dụng:
  bash deploy/ec2/deploy_application.sh [--dry-run] [--migrate] [--seed] [--import-catalog]

Tùy chọn:
  --dry-run         Chỉ in các lệnh sẽ chạy; không thay đổi source/database/cache.
  --migrate         Chạy migration production. Chỉ dùng sau backup và khi .env trỏ đúng database Laravel.
  --seed            Chạy DatabaseSeeder sau migration (chỉ dành cho database Laravel mới).
  --import-catalog  Import catalog từ resources/catalog (chỉ chạy sau khi xem dry-run).
USAGE
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=true ;;
        --migrate) MIGRATE=true ;;
        --seed) SEED=true ;;
        --import-catalog) IMPORT_CATALOG=true ;;
        -h|--help) usage; exit 0 ;;
        *) printf 'Tùy chọn không hợp lệ: %s\n' "$1" >&2; usage >&2; exit 2 ;;
    esac
    shift
done

if [[ "${SEED}" == true && "${MIGRATE}" == false ]]; then
    printf 'FAIL: --seed yêu cầu --migrate để tránh seed nhầm database.\n' >&2
    exit 2
fi

run() {
    printf '+ '
    printf '%q ' "$@"
    printf '\n'
    if [[ "${DRY_RUN}" == false ]]; then
        "$@"
    fi
}

cd "${PROJECT_ROOT}"

if [[ ! -f .env ]]; then
    printf 'FAIL: Không có .env. Tạo .env từ .env.production.example trên EC2 trước.\n' >&2
    exit 1
fi

if [[ "${DRY_RUN}" == false ]]; then
    app_env="$(grep -E '^APP_ENV=' .env | tail -n 1 | cut -d= -f2- | tr -d '\"\047')"
    if [[ "${app_env}" != 'production' ]]; then
        printf 'FAIL: Chỉ deploy khi APP_ENV=production. Hiện tại: %s\n' "${app_env:-trống}" >&2
        exit 1
    fi
fi

command -v php >/dev/null 2>&1 || { printf 'FAIL: Không tìm thấy php.\n' >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { printf 'FAIL: Không tìm thấy composer.\n' >&2; exit 1; }

if [[ "${DRY_RUN}" == true ]]; then
    printf 'DRY-RUN: Không có lệnh thay đổi hệ thống/database nào được thực thi.\n'
fi

run composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
run php artisan optimize:clear

if [[ "${MIGRATE}" == true ]]; then
    run php artisan migrate --force --no-interaction
else
    printf 'INFO: Bỏ qua migration. Dùng --migrate chỉ sau khi backup và xác minh database Laravel.\n'
fi

if [[ "${SEED}" == true ]]; then
    run php artisan db:seed --force --no-interaction
fi

if [[ "${IMPORT_CATALOG}" == true ]]; then
    run php artisan catalog:import
fi

run php artisan config:cache
run php artisan route:cache
run php artisan view:cache

if [[ "${DRY_RUN}" == false ]]; then
    bash deploy/ec2/application_readiness.sh
else
    printf '+ bash deploy/ec2/application_readiness.sh\n'
fi

if [[ "${DRY_RUN}" == true ]]; then
    printf 'DEPLOY: DRY-RUN PASS\n'
else
    printf 'DEPLOY: PASS\n'
fi
