<?php

use Dotenv\Dotenv;

// One-time local setup. Never resets an existing database or reads archived settings.
require __DIR__.'/../vendor/autoload.php';

set_exception_handler(function (Throwable $error): void {
    $reason = $error instanceof PDOException ? 'database operation failed (SQLSTATE '.$error->getCode().')' : $error->getMessage();
    fwrite(STDERR, 'Provisioning stopped: '.$reason." No existing database was reset. Review any partial setup before retrying.\n");
    exit(1);
});

$root = dirname(__DIR__);
$appFile = $root.'/.env';
$testFile = $root.'/.env.testing';
$appSettings = Dotenv::parse(file_get_contents($appFile));
if (($appSettings['APP_ENV'] ?? '') !== 'local' || file_exists($testFile)) {
    throw new RuntimeException('Requires a local .env and no existing .env.testing. Review existing setup manually.');
}
if (($appSettings['DB_DATABASE'] ?? '') !== 'thesis_consultation_week13'
    || ($appSettings['DB_USERNAME'] ?? '') !== 'thesis_week13'
    || ($appSettings['DB_CONNECTION'] ?? '') !== 'mysql'
    || ($appSettings['DB_HOST'] ?? '') !== '127.0.0.1'
    || ($appSettings['DB_PORT'] ?? '') !== '3306'
    || ($appSettings['DB_PASSWORD'] ?? '') !== '') {
    throw new RuntimeException('The app database settings are already customized; refusing to overwrite them.');
}

$admin = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', getenv('DB_SETUP_USER') ?: 'root', getenv('DB_SETUP_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$names = ['thesis_consultation_week13', 'thesis_consultation_week13_test'];
foreach ($names as $name) {
    $check = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $check->execute([$name]);
    if ($check->fetchColumn() > 0) {
        throw new RuntimeException('A target database already exists; refusing to reuse or reset it.');
    }
}
foreach (['thesis_week13', 'thesis_week13_test'] as $user) {
    $check = $admin->prepare('SELECT COUNT(*) FROM mysql.user WHERE User = ?');
    $check->execute([$user]);
    if ($check->fetchColumn() > 0) {
        throw new RuntimeException('A target database account already exists; refusing to alter it.');
    }
}

$appPassword = bin2hex(random_bytes(24));
$testPassword = bin2hex(random_bytes(24));
$demoPassword = bin2hex(random_bytes(16));
foreach ([[$names[0], 'thesis_week13', $appPassword], [$names[1], 'thesis_week13_test', $testPassword]] as [$database, $user, $password]) {
    // Identifiers are fixed above; secrets are generated and quoted, never printed.
    $admin->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $account = $admin->quote($user)."@'127.0.0.1'";
    $admin->exec('CREATE USER '.$account.' IDENTIFIED BY '.$admin->quote($password));
    $admin->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON `$database`.* TO $account");
}

$appText = file_get_contents($appFile);
$appText = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD='.$appPassword, $appText);
$appText = preg_replace('/^DEMO_PASSWORD=.*\R?/m', '', $appText);
file_put_contents($appFile, rtrim($appText).PHP_EOL.'DEMO_PASSWORD='.$demoPassword.PHP_EOL);
$testText = file_get_contents($root.'/.env.testing.example');
$testText = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD='.$testPassword, $testText);
$testText = preg_replace('/^DEMO_PASSWORD=.*$/m', 'DEMO_PASSWORD='.$demoPassword, $testText);
$testText = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $testText);
file_put_contents($testFile, $testText);
echo "Created two new isolated databases and database-scoped users. Fresh secrets saved only to ignored .env and .env.testing.\n";
