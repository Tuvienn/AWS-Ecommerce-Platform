#!/usr/bin/env bash
# Kiểm tra không thay đổi trạng thái trước khi đưa Laravel lên EC2.
# Chạy tại thư mục gốc repository: bash deploy/ec2/application_readiness.sh

set -u -o pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV_FILE="${PROJECT_ROOT}/.env"
FAILURES=0

pass() {
    printf 'PASS: %s\n' "$1"
}

fail() {
    printf 'FAIL: %s\n' "$1" >&2
    FAILURES=$((FAILURES + 1))
}

env_value() {
    local key="$1"
    local line value
    line="$(grep -E "^${key}=" "${ENV_FILE}" 2>/dev/null | tail -n 1 || true)"
    value="${line#*=}"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    printf '%s' "${value}"
}

is_placeholder() {
    local value="$1"
    [[ -z "${value}" || "${value}" == YOUR_* || "${value}" == CHANGE_ME* || "${value}" == *"example.com"* ]]
}

check_php_extension() {
    local extension="$1"
    if php -r "exit(extension_loaded('${extension}') ? 0 : 1);"; then
        pass "PHP extension ${extension}"
    else
        fail "Thiếu PHP extension ${extension}"
    fi
}

cd "${PROJECT_ROOT}"

if ! command -v php >/dev/null 2>&1; then
    fail 'Không tìm thấy php trong PATH.'
else
    if php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'; then
        pass "PHP $(php -r 'echo PHP_VERSION;') >= 8.3"
    else
        fail "PHP $(php -r 'echo PHP_VERSION;') không đáp ứng PHP 8.3 trở lên."
    fi

    check_php_extension pdo_mysql
    check_php_extension mbstring
    check_php_extension fileinfo
    check_php_extension openssl
fi

if [[ ! -f "${ENV_FILE}" ]]; then
    fail 'Không có file .env. Hãy copy .env.production.example thành .env và cấu hình secret trên EC2.'
else
    app_env="$(env_value APP_ENV)"
    app_debug="$(env_value APP_DEBUG)"
    app_key="$(env_value APP_KEY)"
    app_url="$(env_value APP_URL)"

    [[ "${app_env}" == 'production' ]] && pass 'APP_ENV=production' || fail 'APP_ENV phải là production.'
    [[ "${app_debug}" == 'false' || "${app_debug}" == '0' ]] && pass 'APP_DEBUG đã tắt' || fail 'APP_DEBUG phải là false ở production.'
    ! is_placeholder "${app_key}" && pass 'APP_KEY đã cấu hình' || fail 'APP_KEY còn trống/placeholder.'
    [[ "${app_url}" =~ ^https?:// ]] && ! is_placeholder "${app_url}" && pass 'APP_URL hợp lệ' || fail 'APP_URL chưa hợp lệ hoặc còn placeholder.'

    for key in DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
        value="$(env_value "${key}")"
        ! is_placeholder "${value}" && pass "${key} đã cấu hình" || fail "${key} còn trống/placeholder."
    done

    # Nếu biến không được khai báo, giữ đúng default của config/catalog.php.
    catalog_disk="$(env_value CATALOG_DISK)"
    catalog_disk="${catalog_disk:-public}"
    if [[ "${catalog_disk}" == 's3' ]]; then
        for key in AWS_DEFAULT_REGION AWS_BUCKET; do
            value="$(env_value "${key}")"
            ! is_placeholder "${value}" && pass "${key} đã cấu hình" || fail "${key} còn trống/placeholder cho S3."
        done

        if php -r "require 'vendor/autoload.php'; exit(class_exists('League\\\\Flysystem\\\\AwsS3V3\\\\AwsS3V3Adapter') ? 0 : 1);"; then
            pass 'S3 Flysystem adapter đã cài'
        else
            fail 'CATALOG_DISK=s3 nhưng chưa có league/flysystem-aws-s3-v3. Hoàn tất Change 4 trước.'
        fi
    elif [[ "${catalog_disk}" == 'public' ]]; then
        pass 'CATALOG_DISK=public'
    else
        fail 'CATALOG_DISK chỉ được là public hoặc s3.'
    fi
fi

for path in storage storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache; do
    if [[ -d "${path}" && -w "${path}" ]]; then
        pass "Có quyền ghi ${path}"
    else
        fail "Thiếu thư mục hoặc không có quyền ghi ${path}"
    fi
done

if [[ -f composer.lock ]]; then
    pass 'Có composer.lock để cài dependency tái lập được'
else
    fail 'Thiếu composer.lock.'
fi

if [[ -f "${ENV_FILE}" ]] && command -v php >/dev/null 2>&1; then
    if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
        pass 'Kết nối database và migration status thành công'
    else
        fail 'Không thể kết nối database hoặc đọc migration status.'
    fi
fi

if [[ ${FAILURES} -gt 0 ]]; then
    printf 'READINESS: FAIL (%d kiểm tra)\n' "${FAILURES}" >&2
    exit 1
fi

printf 'READINESS: PASS\n'
