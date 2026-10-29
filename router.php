<?php
declare(strict_types=1);
// Development only: php -S 127.0.0.1:31783 -t public router.php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/api' || str_starts_with($path, '/api/')) {
    require __DIR__ . '/public/api/index.php';
    return true;
}
$public = realpath(__DIR__ . '/public');
$candidate = realpath(__DIR__ . '/public' . rawurldecode($path));
if ($public && $candidate && str_starts_with($candidate, $public . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    return false;
}
if ($path !== '/' && str_contains(rawurldecode($path), '..')) {
    http_response_code(404);
    return true;
}
if (is_file(__DIR__ . '/public/index.html')) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/public/index.html');
    return true;
}
http_response_code(404);
echo 'Not found';
