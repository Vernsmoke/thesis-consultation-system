<?php

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';
$root = dirname(__DIR__);
$appFile = $root.'/.env';
$serviceFile = dirname($root).'/notification-service/.env';
$settings = Dotenv::parse(file_get_contents($appFile));
if (($settings['APP_ENV'] ?? '') !== 'local' || file_exists($serviceFile) || ! empty($settings['NOTIFICATION_SERVICE_KEY'])) {
    throw new RuntimeException('Existing/private service configuration found or not local; review manually, do not rotate automatically.');
}
$key = bin2hex(random_bytes(32));
file_put_contents($serviceFile, 'SERVICE_KEY='.$key.PHP_EOL);
file_put_contents($appFile, PHP_EOL.'NOTIFICATION_SERVICE_URL=http://127.0.0.1:8090/notify'.PHP_EOL.'NOTIFICATION_SERVICE_KEY='.$key.PHP_EOL, FILE_APPEND);
echo "Fresh shared key saved only to ignored local configuration; no secret printed.\n";
