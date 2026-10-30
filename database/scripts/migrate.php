<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Migration runner chỉ được chạy bằng PHP CLI.\n");
    exit(1);
}

$command = $argv[1] ?? 'status';
if (!in_array($command, ['status', 'up'], true)) {
    fwrite(STDERR, "Cách dùng: php database/scripts/migrate.php [status|up]\n");
    exit(1);
}

$projectRoot = dirname(__DIR__, 2);
$databaseConfiguration = require $projectRoot . '/config/database.php';
$environment = getenv('APP_ENV');

if ($environment === 'production') {
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $requiredVariable) {
        $value = getenv($requiredVariable);
        if ($value === false || trim($value) === '') {
            fwrite(STDERR, "Thiếu biến môi trường bắt buộc: {$requiredVariable}\n");
            exit(1);
        }
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
    $multiStatementsAttribute = defined('Pdo\\Mysql::ATTR_MULTI_STATEMENTS')
        ? constant('Pdo\\Mysql::ATTR_MULTI_STATEMENTS')
        : PDO::MYSQL_ATTR_MULTI_STATEMENTS;
    $connection = new PDO(
        $dsn,
        $databaseConfiguration['user'],
        $databaseConfiguration['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            $multiStatementsAttribute => true,
        ]
    );
} catch (PDOException $exception) {
    fwrite(STDERR, "Không thể kết nối database để migration.\n");
    exit(1);
}

$migrationDirectory = $projectRoot . '/database/migrations';
$migrationFiles = glob($migrationDirectory . '/*_up.sql') ?: [];
sort($migrationFiles, SORT_STRING);

$tableExistsStatement = $connection->prepare(
    'SELECT COUNT(*) FROM information_schema.tables '
    . 'WHERE table_schema = DATABASE() AND table_name = ?'
);
$tableExistsStatement->execute(['schema_migrations']);
$migrationTableExists = (int) $tableExistsStatement->fetchColumn() === 1;

if ($command === 'status') {
    $appliedVersions = [];
    if ($migrationTableExists) {
        $appliedVersions = $connection
            ->query('SELECT version FROM schema_migrations ORDER BY version')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    foreach ($migrationFiles as $migrationFile) {
        $version = basename($migrationFile, '_up.sql');
        $status = in_array($version, $appliedVersions, true) ? 'APPLIED' : 'PENDING';
        echo sprintf("%-10s %s\n", $status, $version);
    }
    exit(0);
}

$lockName = 'webbanhang_schema_migration';
$lockStatement = $connection->prepare('SELECT GET_LOCK(?, 10)');
$lockStatement->execute([$lockName]);
if ((int) $lockStatement->fetchColumn() !== 1) {
    fwrite(STDERR, "Không lấy được migration lock.\n");
    exit(1);
}

try {
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations ('
        . 'version VARCHAR(100) PRIMARY KEY, '
        . 'applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
        . ') ENGINE=InnoDB'
    );

    $appliedVersions = $connection
        ->query('SELECT version FROM schema_migrations ORDER BY version')
        ->fetchAll(PDO::FETCH_COLUMN);
    $recordMigration = $connection->prepare(
        'INSERT INTO schema_migrations (version) VALUES (?)'
    );

    foreach ($migrationFiles as $migrationFile) {
        $version = basename($migrationFile, '_up.sql');
        if (in_array($version, $appliedVersions, true)) {
            echo "SKIP {$version}\n";
            continue;
        }

        $sql = file_get_contents($migrationFile);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException("Migration rỗng hoặc không đọc được: {$version}");
        }

        echo "APPLY {$version}\n";
        $connection->exec($sql);
        $recordMigration->execute([$version]);
        echo "DONE  {$version}\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration thất bại: {$exception->getMessage()}\n");
    exit(1);
} finally {
    $releaseStatement = $connection->prepare('SELECT RELEASE_LOCK(?)');
    $releaseStatement->execute([$lockName]);
}
