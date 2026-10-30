#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${project_root}"

echo "[1/4] PHP lint"
find app config database includes public resources routes tests -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

echo "[2/4] Route registry"
php -r '$root=getcwd(); foreach (["routes/web.php","routes/admin.php","routes/ajax.php"] as $file) { foreach (require $file as $target) { if (!is_file($target)) { fwrite(STDERR, "Missing route target: {$target}\n"); exit(1); } } }'

echo "[3/4] Local storage integration"
php tests/integration/phase4_local_storage.php

if [[ "${RUN_DATABASE_TESTS:-false}" == "true" ]]; then
    echo "[4/4] Database concurrency regression"
    php tests/integration/phase3_concurrency.php
else
    echo "[4/4] Bỏ qua database regression (đặt RUN_DATABASE_TESTS=true nếu database đã sẵn sàng)."
fi

echo "REGRESSION: PASS"
