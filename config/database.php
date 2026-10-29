<?php

$getEnvironmentValue = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);

    return $value === false ? $default : $value;
};

return [
    'host' => $getEnvironmentValue('DB_HOST', 'localhost'),
    'port' => $getEnvironmentValue('DB_PORT', '3306'),
    'name' => $getEnvironmentValue('DB_NAME', 'webbanhang'),
    'user' => $getEnvironmentValue('DB_USER', 'root'),
    'password' => $getEnvironmentValue('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];

