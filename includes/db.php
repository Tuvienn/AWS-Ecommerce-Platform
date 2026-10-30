<?php
require_once __DIR__ . '/bootstrap.php';

$appConfiguration = $GLOBALS['project_config']['app'];
$databaseConfiguration = $GLOBALS['project_config']['database'];

if ($appConfiguration['environment'] === 'production') {
    $requiredVariables = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    $missingVariables = [];

    foreach ($requiredVariables as $variable) {
        $value = getenv($variable);
        if ($value === false || trim($value) === '') {
            $missingVariables[] = $variable;
        }
    }

    if ($missingVariables) {
        error_log('Thiếu biến môi trường database bắt buộc: ' . implode(', ', $missingVariables));

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Thiếu biến môi trường database bắt buộc: ' . implode(', ', $missingVariables) . PHP_EOL);
            exit(1);
        }

        http_response_code(500);
        exit('Hệ thống đang tạm thời không khả dụng. Vui lòng thử lại sau.');
    }
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $databaseConfiguration['host'],
    $databaseConfiguration['port'],
    $databaseConfiguration['name'],
    $databaseConfiguration['charset']
);

try {
    $conn = new PDO(
        $dsn,
        $databaseConfiguration['user'],
        $databaseConfiguration['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Kết nối database thất bại: ' . $e->getMessage());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Kết nối database thất bại: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);

    if ($appConfiguration['debug']) {
        exit('Kết nối thất bại: ' . $e->getMessage());
    }

    exit('Hệ thống đang tạm thời không khả dụng. Vui lòng thử lại sau.');
}
