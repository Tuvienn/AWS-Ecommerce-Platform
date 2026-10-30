<?php

$composerAutoload = PROJECT_ROOT . '/vendor/autoload.php';
if (!is_file($composerAutoload)) {
    throw new RuntimeException('Thiếu Composer autoload. Hãy chạy composer install.');
}

require_once $composerAutoload;
