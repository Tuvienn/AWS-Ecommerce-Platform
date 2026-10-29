#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${project_root}"

failures=0
pass() { printf 'PASS: %s\n' "$1"; }
fail() { printf 'FAIL: %s\n' "$1" >&2; failures=$((failures + 1)); }

for required_file in public/index.php public/products.php public/product_detail.php includes/bootstrap.php config/app.php config/database.php config/storage.php database/check_catalog_assets.php; do
    if [[ -f "${required_file}" ]]; then pass "Có ${required_file}"; else fail "Thiếu ${required_file}"; fi
done

php_version="$(php -r 'echo PHP_VERSION;')"
if php -r 'exit(version_compare(PHP_VERSION, "8.1.0", ">=") ? 0 : 1);'; then pass "PHP ${php_version} >= 8.1"; else fail "PHP ${php_version} < 8.1"; fi

for extension in pdo_mysql fileinfo curl mbstring; do
    if php -m | grep -Fxi "${extension}" >/dev/null; then pass "PHP extension ${extension}"; else fail "Thiếu PHP extension ${extension}"; fi
done

for writable_directory in storage/logs storage/uploads uploads/avatars; do
    if [[ -d "${writable_directory}" && -w "${writable_directory}" ]]; then pass "Writable ${writable_directory}"; else fail "Không writable ${writable_directory}"; fi
done

if [[ -d public/app || -d public/config || -d public/database || -d public/includes || -d public/storage ]]; then
    fail "Có internal directory bị lộ trong public/"
else
    pass "Internal directories nằm ngoài public/"
fi

if git ls-files --error-unmatch .env >/dev/null 2>&1; then fail ".env đang được track"; else pass "Không track .env"; fi

lint_output="$(mktemp)"
trap 'rm -f "${lint_output}"' EXIT
if find public app config includes database tests -name '*.php' -print0 | xargs -0 -n1 php -l >"${lint_output}" 2>&1; then
    pass "PHP lint toàn bộ source"
else
    fail "PHP lint thất bại (xem output bên dưới)"
    cat "${lint_output}" >&2
fi

if php database/check_catalog_assets.php >/tmp/aws-ecommerce-catalog-health.json; then
    pass "Catalog asset health check PASS"
else
    fail "Catalog asset health check FAIL"
fi

if [[ "${failures}" -eq 0 ]]; then
    printf 'READINESS: PASS\n'
    exit 0
fi
printf 'READINESS: FAIL (%s checks)\n' "${failures}" >&2
exit 1
