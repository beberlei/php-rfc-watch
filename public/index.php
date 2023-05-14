<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env', );
}

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ($env !== 'prod'));

if ($debug) {
    umask(0000);

    Debug::enable();
}

$trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false;
if ($trustedProxies !== false) {
    Request::setTrustedProxies(
        explode(',', $trustedProxies),
        Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
    );
}

$trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false;
if ($trustedHosts !== false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
