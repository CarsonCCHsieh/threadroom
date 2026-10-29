<?php
declare(strict_types=1);
// Read-only only. Never prints credentials, account identifiers, or signed URLs.
require dirname(__DIR__) . '/server/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
try {
    $config = new \Threadroom\Config();
    if (!$config->configured()) throw new \Threadroom\ApiError(503, 'not_configured', 'No server-side API key is configured.');
    $p = (new \Threadroom\YouCam($config, new \Threadroom\CurlTransport()))->preflight();
    echo json_encode(['ok' => true, 'provider' => 'YouCam', 'engine' => 'cloth-v3',
        'verifiedUnitCost' => $p['cost'], 'judgingReserveProtected' => true,
        'liveEnabled' => $config->get('live'), 'chargedTaskCreated' => false], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Threadroom\ApiError $e) {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => $e->kind, 'message' => $e->getMessage()]) . PHP_EOL); exit(1);
} catch (\Throwable) {
    fwrite(STDERR, '{"ok":false,"error":"check_failed","message":"The read-only provider check could not complete."}' . PHP_EOL); exit(1);
}
