<?php
/**
 * Front controller: every request enters here.
 */
declare(strict_types=1);

// PHP built-in server: let it serve real static files (CSS, JS, images) itself.
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($staticFile) && pathinfo($staticFile, PATHINFO_EXTENSION) !== 'php') {
        return false;
    }
}

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Dependencies missing. Run "composer install" (or "composer dump-autoload") in the project root.');
}
require $autoload;

use App\Controllers\UserController;
use Core\View;

// Never show raw PHP errors to visitors; they go to the error log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Security headers (the app serves no inline scripts or styles, so the CSP can be strict).
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_start([
    'cookie_httponly'  => true,
    'cookie_samesite'  => 'Lax',
    'cookie_secure'    => $isHttps,
    'use_strict_mode'  => true,
]);

// Route table: [HTTP method, regex, controller method]
$routes = [
    ['GET',  '#^/users$#',                 'index'],
    ['GET',  '#^/users/create$#',          'create'],
    ['POST', '#^/users$#',                 'store'],
    ['GET',  '#^/users/(\d+)/edit$#',      'edit'],
    ['POST', '#^/users/(\d+)/update$#',    'update'],
    ['POST', '#^/users/(\d+)/delete$#',    'destroy'],
];

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

try {
    if ($path === '/') {
        redirect('/users');
    }

    foreach ($routes as [$routeMethod, $pattern, $action]) {
        if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
            array_shift($matches);                       // drop full match
            $args = array_map('intval', $matches);       // route params are ints
            (new UserController())->$action(...$args);
            exit;
        }
    }

    View::error(404, 'Page not found.');
} catch (Throwable $e) {
    error_log($e::class . ': ' . $e->getMessage());
    View::error(500, 'Something went wrong on our side. Please try again later.');
}
