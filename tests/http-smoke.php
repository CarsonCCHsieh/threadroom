<?php
declare(strict_types=1);
// Isolated, no-key local HTTP process. No provider network is possible in this test.
$root = sys_get_temp_dir() . '/threadroom-http-tests-' . bin2hex(random_bytes(8));
mkdir($root . '/public/api', 0700, true); mkdir($root . '/server', 0700, true);
$source = dirname(__DIR__);
foreach (['Support.php', 'Image.php', 'YouCam.php', 'Api.php', 'bootstrap.php'] as $file) copy($source . '/server/' . $file, $root . '/server/' . $file);
copy($source . '/public/api/index.php', $root . '/public/api/index.php'); copy($source . '/router.php', $root . '/router.php');
file_put_contents($root . '/public/index.html', 'ISOLATED_FRONTEND_FIXTURE');
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$socket) throw new RuntimeException('No local port available.');
$address = stream_socket_get_name($socket, false); $port = (int)substr(strrchr($address, ':'), 1); fclose($socket);
$env = array_merge(getenv(), ['YOUCAM_API_KEY' => '', 'YOUCAM_LIVE_ENABLED' => 'false',
    'THREADROOM_SECURE_COOKIE' => 'false', 'THREADROOM_RUNTIME_DIR' => $root . '/var/private']);
$pipes = [];
$process = proc_open([PHP_BINARY, '-d', 'upload_max_filesize=10M', '-d', 'post_max_size=21M', '-S', '127.0.0.1:' . $port,
    '-t', $root . '/public', $root . '/router.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
if (!is_resource($process)) throw new RuntimeException('Local test server did not start.');
fclose($pipes[0]);
function assertHttp(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function requestHttp(string $method, string $path, array $headers = [], string $body = ''): array {
    global $port;
    $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers),
        'content' => $body, 'ignore_errors' => true, 'timeout' => 3, 'follow_location' => 0]]);
    $result = @file_get_contents('http://127.0.0.1:' . $port . $path, false, $context);
    $responseHeaders = $http_response_header ?? [];
    return ['body' => $result, 'headers' => $responseHeaders, 'status' => isset($responseHeaders[0]) ? (int)explode(' ', $responseHeaders[0])[1] : 0];
}
try {
    $health = null;
    for ($i = 0; $i < 30; $i++) { $health = requestHttp('GET', '/api/health'); if ($health['status'] !== 0) break; usleep(100000); }
    assertHttp($health['status'] === 200, 'Health endpoint responds');
    $json = json_decode($health['body'], true, 16, JSON_THROW_ON_ERROR);
    assertHttp($json['configured'] === false && $json['liveAvailable'] === false, 'No-key live service is honest');
    assertHttp(strlen($json['csrf']) === 64, 'CSRF token created');
    $setCookie = '';
    foreach ($health['headers'] as $header) if (str_starts_with(strtolower($header), 'set-cookie:')) $setCookie = $header;
    assertHttp(str_contains(strtolower($setCookie), 'httponly') && str_contains(strtolower($setCookie), 'samesite=strict'), 'HttpOnly SameSite session');
    preg_match('/threadroom_session=[^;]+/', $setCookie, $match); $cookie = 'Cookie: ' . ($match[0] ?? '');
    assertHttp(requestHttp('DELETE', '/api/session', [$cookie])['status'] === 403, 'CSRF required on HTTP mutation');
    $headers = [$cookie, 'X-CSRF-Token: ' . $json['csrf']];
    assertHttp(requestHttp('DELETE', '/api/session', array_merge($headers, ['Origin: https://evil.example']))['status'] === 403, 'Foreign Origin rejected');
    assertHttp(requestHttp('DELETE', '/api/session', $headers)['status'] === 200, 'Owned session can be cleared');
    $boundary = 'threadroom-' . bin2hex(random_bytes(8)); $body = '';
    foreach (['consent' => 'true', 'category' => 'upper_body', 'idempotency_key' => '12345678-1234-4123-8123-123456789012'] as $name => $value) {
        $body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n" . $value . "\r\n";
    }
    foreach (['person', 'garment'] as $name) $body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $name
        . "\"; filename=\"photo.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n" . file_get_contents(__DIR__ . '/fixtures/result.jpg') . "\r\n";
    $body .= '--' . $boundary . "--\r\n";
    $upload = requestHttp('POST', '/api/tryons', array_merge($headers, ['Content-Type: multipart/form-data; boundary=' . $boundary]), $body);
    assertHttp($upload['status'] === 503 && json_decode($upload['body'], true)['error'] === 'live_unavailable', 'Real multipart upload validates but no-key never starts provider');
    assertHttp(!str_contains((string)requestHttp('GET', '/server/Support.php')['body'], '<?php'), 'Private source is never served');
    echo 'PASS isolated HTTP session, CSRF, Origin, multipart and no-key smoke; zero provider calls.' . PHP_EOL;
} finally {
    proc_terminate($process); fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
    $real = realpath($root);
    if ($real && str_starts_with(basename($real), 'threadroom-http-tests-')) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($real, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) { if ($file->isDir()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
        rmdir($real);
    }
}
