<?php
declare(strict_types=1);
use Threadroom\Api;
use Threadroom\ApiError;
use Threadroom\Config;
require dirname(__DIR__, 2) . '/server/bootstrap.php';

ini_set('display_errors', '0');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cross-Origin-Resource-Policy: same-origin');

try {
    $config = new Config();
    $sessionDir = $config->get('runtime') . '/sessions';
    if (!is_dir($sessionDir) && !mkdir($sessionDir, 0700, true) && !is_dir($sessionDir)) throw new \RuntimeException('Session storage unavailable.');
    @chmod($sessionDir, 0700);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_save_path($sessionDir);
    session_name('threadroom_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $config->get('secureCookie'),
        'httponly' => true, 'samesite' => 'Strict']);
    if (!session_start()) throw new \RuntimeException('Session unavailable.');
    if (!isset($_SESSION['owner'], $_SESSION['csrf'])) {
        session_regenerate_id(true);
        $_SESSION['owner'] = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    $owner = $_SESSION['owner']; $csrf = $_SESSION['csrf'];
    session_write_close(); // Release the session lock before provider I/O.
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $headers = ['x-csrf-token' => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''];
    if ($method !== 'GET') {
        if (!hash_equals($csrf, $headers['x-csrf-token'])) throw new ApiError(403, 'csrf', 'Refresh the page before trying again.');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if ($origin !== null) {
            $p = parse_url($origin);
            $originAuthority = strtolower(($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : ''));
            if (!$p || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || $originAuthority !== strtolower($_SERVER['HTTP_HOST'] ?? '')) {
                throw new ApiError(403, 'origin', 'This request must come from the application.');
            }
        }
    }
    $uploads = [];
    if ($method === 'POST' && $path === '/api/tryons') {
        if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data;')) throw new ApiError(415, 'content_type', 'Upload photos using the application form.');
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 20500000) throw new ApiError(413, 'request_too_large', 'The uploaded photos are too large.');
        foreach (['person', 'garment'] as $name) {
            $f = $_FILES[$name] ?? null;
            if (!is_array($f) || !isset($f['tmp_name'], $f['error'], $f['size']) || !is_string($f['tmp_name'])
                || $f['error'] !== UPLOAD_ERR_OK || $f['size'] >= \Threadroom\Image::MAX_BYTES || !is_uploaded_file($f['tmp_name'])) {
                throw new ApiError(422, 'invalid_upload', 'Choose a JPEG or PNG smaller than 10 MB for each photo.');
            }
            $bytes = file_get_contents($f['tmp_name'], false, null, 0, \Threadroom\Image::MAX_BYTES);
            if ($bytes === false) throw new ApiError(422, 'invalid_upload', 'The uploaded photo could not be read.');
            $uploads[$name] = $bytes;
        }
    }
    $response = (new Api($config))->dispatch($method, $path, $owner, $csrf, $headers, $_POST, $uploads);
    http_response_code($response['http']);
    if (isset($response['file'])) {
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="threadroom-tryon.jpg"');
        header('Content-Length: ' . filesize($response['file']));
        readfile($response['file']);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response['json'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
} catch (ApiError $e) {
    http_response_code($e->http);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->kind, 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES);
} catch (\Throwable) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"error":"temporarily_unavailable","message":"The private try-on service is temporarily unavailable."}';
}
