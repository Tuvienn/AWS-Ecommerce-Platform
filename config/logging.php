<?php

$configuredLogPath = getenv('LOG_PATH');

if ($configuredLogPath === false || trim($configuredLogPath) === '') {
    $configuredLogPath = dirname(__DIR__) . '/storage/logs/application.log';
}

return [
    'path' => $configuredLogPath,
];

