<?php

$getEnvironmentValue = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);

    return $value === false ? $default : $value;
};

$environment = $getEnvironmentValue('APP_ENV', 'local');
$applicationUrl = rtrim($getEnvironmentValue('APP_URL', 'http://127.0.0.1:8000'), '/');
$basePath = trim($getEnvironmentValue('APP_BASE_PATH', ''));

if ($basePath === '' || $basePath === '/') {
    $basePath = '';
} else {
    $basePath = '/' . trim($basePath, '/');
}

return [
    'environment' => $environment,
    'url' => $applicationUrl,
    'base_path' => $basePath,
    'timezone' => $getEnvironmentValue('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'debug' => $environment !== 'production',
];

