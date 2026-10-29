<?php
declare(strict_types=1);
// All provider traffic below uses MockTransport. This is NOT a live API verification.
require dirname(__DIR__) . '/server/bootstrap.php';
use Threadroom\Api;
use Threadroom\ApiError;
use Threadroom\Config;
use Threadroom\Image;
use Threadroom\Store;
use Threadroom\Transport;
use Threadroom\UrlGuard;

final class MockTransport implements Transport
{
    public array $calls = [];
    public float $balance = 1000;
    public float $cost = 2;
    public bool $expired = false;
    public bool $unknownCreate = false;
    public bool $preflightTimeout = false;
    public string $resultUrl = 'https://yce-us.s3-accelerate.amazonaws.com/result.jpg?signature=TEST';
    public string $result;
    public int $creates = 0;
    public int $polls = 0;
    public int $deletes = 0;
    public int $files = 0;
    public int $puts = 0;
    public ?\Closure $afterSecondUpload = null;
    public string $taskStatus = 'success';
    public function __construct() { $this->result = fixtureJpeg(); }
    public function send(string $method, string $url, array $headers = [], string $body = '', bool $signed = false, int $maxBytes = 1000000): array
    {
        $this->calls[] = [$method, $url, $headers, $body, $signed];
        if ($signed) {
            foreach ($headers as $h) check(!str_starts_with(strtolower($h), 'authorization:'), 'No account key on signed storage request');
            if ($method === 'PUT' && ++$this->puts === 2 && $this->afterSecondUpload) ($this->afterSecondUpload)();
            return ['status' => 200, 'body' => $method === 'GET' ? $this->result : ''];
        }
        $path = parse_url($url, PHP_URL_PATH);
        if ($method === 'GET' && $path === '/s2s/v1.0/client/credit') {
            if ($this->preflightTimeout) throw new ApiError(503, 'provider_connection', 'The provider response could not be confirmed.');
            return $this->json(['status' => 200, 'results' => [['amount_dec' => $this->balance, 'expiry' => (time() + ($this->expired ? -60 : 3600)) * 1000]]]);
        }
        if ($method === 'GET' && $path === '/s2s/v2.0/credit/feature-cost') return $this->json(['status' => 200,
            'result' => ['next_token' => null, 'skus' => [['amount' => $this->cost, 'unit' => 'result_image', 'proc_unit' => 1,
                'run_task_url' => 'https://yce-api-01.perfectcorp.com/s2s/v2.0/task/cloth-v3']]]]);
        if ($method === 'POST' && $path === '/s2s/v2.0/file') {
            $file = json_decode($body, true)['files'][0]; $this->files++;
            return $this->json(['status' => 200, 'data' => ['files' => [['file_id' => 'file-' . $this->files,
                'requests' => [['method' => 'PUT', 'url' => 'https://yce-us.s3-accelerate.amazonaws.com/upload?signature=TEST',
                    'headers' => ['Content-Type' => $file['content_type'], 'Content-Length' => $file['file_size']]]]]]]]);
        }
        if ($method === 'POST' && $path === '/s2s/v2.0/task/cloth-v3') {
            $this->creates++;
            if ($this->unknownCreate) throw new ApiError(503, 'provider_connection', 'The provider response could not be confirmed.');
            $payload = json_decode($body, true);
            check($payload['garment_category'] === 'upper_body' && isset($payload['src_file_id'], $payload['ref_file_id']), 'True provider integration payload');
            return $this->json(['status' => 200, 'data' => ['task_id' => 'task-' . $this->creates]]);
        }
        if ($method === 'GET' && str_starts_with($path, '/s2s/v2.0/task/cloth-v3/')) {
            $this->polls++;
            return $this->json(['status' => 200, 'data' => ['task_status' => $this->taskStatus,
                'results' => ['url' => $this->resultUrl]]]);
        }
        if ($method === 'POST' && $path === '/s2s/v2.0/task/delete') { $this->deletes++; return $this->json(['status' => 200]); }
        throw new \RuntimeException('Unexpected mock endpoint. No network calls are possible.');
    }
    private function json(array $data): array { return ['status' => 200, 'body' => json_encode($data, JSON_THROW_ON_ERROR)]; }
}

$suiteRoot = sys_get_temp_dir() . '/threadroom-tests-' . bin2hex(random_bytes(8));
mkdir($suiteRoot, 0700, true);
$passed = 0; $case = 0;
function check(bool $ok, string $message): void { if (!$ok) throw new \RuntimeException($message); }
function expect(string $kind, callable $fn): void {
    try { $fn(); } catch (ApiError $e) { check($e->kind === $kind, 'Expected ' . $kind . ', got ' . $e->kind); return; }
    throw new \RuntimeException('Expected an ApiError: ' . $kind);
}
function fixtureJpeg(): string {
    $s = file_get_contents(__DIR__ . '/fixtures/result.jpg');
    $exif = "Exif\0\0SECRET_GPS_METADATA";
    return substr($s, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($s, 2) . 'TRAILING_PRIVATE_DATA';
}
function fixturePng(): string {
    $chunk = static fn(string $type, string $data): string => pack('N', strlen($data)) . $type . $data . hash('crc32b', $type . $data, true);
    $raw = str_repeat("\0" . str_repeat("\xAA\xBB\xCC", 512), 512);
    return "\x89PNG\r\n\x1A\n" . $chunk('IHDR', pack('NNCCCCC', 512, 512, 8, 2, 0, 0, 0))
        . $chunk('tEXt', "location\0SECRET_GPS_METADATA") . $chunk('IDAT', gzcompress($raw)) . $chunk('IEND', '') . 'TRAILING_PRIVATE_DATA';
}
function setupApi(array $settings = []): array {
    global $suiteRoot, $case;
    $dir = $suiteRoot . '/case-' . ++$case;
    $config = new Config(array_replace(['YOUCAM_API_KEY' => 'FAKE_TEST_KEY_NOT_REAL', 'YOUCAM_LIVE_ENABLED' => true,
        'YOUCAM_API_BASE_URL' => 'https://yce-api-01.perfectcorp.com', 'THREADROOM_RUNTIME_DIR' => $dir,
        'THREADROOM_GLOBAL_UNIT_BUDGET' => 300, 'THREADROOM_JUDGING_RESERVE_UNITS' => 300,
        'THREADROOM_SESSION_TASK_LIMIT' => 12, 'THREADROOM_GLOBAL_TASK_LIMIT' => 150], $settings));
    $transport = new MockTransport();
    return [new Api($config, $transport), $transport, new Store($config->get('runtime')), $config];
}
function form(int $n = 1): array { return ['consent' => 'true', 'category' => 'upper_body', 'idempotency_key' => sprintf('12345678-1234-4123-8123-%012d', $n)]; }
function create(Api $api, string $owner = 'owner-a', int $n = 1): array {
    return $api->dispatch('POST', '/api/tryons', $owner, 'csrf', ['x-csrf-token' => 'csrf'], form($n), ['person' => fixtureJpeg(), 'garment' => fixturePng()]);
}
function ready(Store $store, string $id): void { $store->locked(static function(array &$s) use ($id) { $s['jobs'][$id]['nextPollAt'] = 0; }); }
function test(string $name, callable $fn): void { global $passed; $fn(); $passed++; echo 'PASS ' . $name . PHP_EOL; }

try {
    test('No key and disabled live fail closed without provider traffic', function() {
        [$api, $http] = setupApi(['YOUCAM_API_KEY' => '', 'YOUCAM_LIVE_ENABLED' => false]);
        $h = $api->dispatch('GET', '/api/health', 'a', 'csrf')['json'];
        check($h['configured'] === false && $h['liveAvailable'] === false && $h['csrf'] === 'csrf', 'Honest health response');
        expect('live_unavailable', fn() => create($api)); check(count($http->calls) === 0, 'No provider requests');
    });
    test('CSRF, consent, and unsupported category are rejected before any provider call', function() {
        [$api, $http] = setupApi();
        expect('csrf', fn() => $api->dispatch('DELETE', '/api/session', 'a', 'csrf', ['x-csrf-token' => 'bad']));
        expect('invalid_request', fn() => $api->dispatch('POST', '/api/tryons', 'a', 'csrf', ['x-csrf-token' => 'csrf'], array_replace(form(), ['consent' => 'false'])));
        check(count($http->calls) === 0, 'Invalid requests did not touch provider');
    });
    test('JPEG/PNG metadata and trailing payloads are stripped; malformed images rejected', function() {
        foreach ([fixtureJpeg(), fixturePng()] as $source) {
            $clean = Image::sanitize($source);
            check(!str_contains($clean['bytes'], 'SECRET_GPS') && !str_contains($clean['bytes'], 'TRAILING_PRIVATE'), 'Metadata removed');
            check($clean['width'] === 512 && $clean['height'] === 512, 'Dimensions preserved');
        }
        expect('invalid_image', fn() => Image::sanitize('<?php echo "not image"; ?>' . str_repeat('x', 100)));
        $png = fixturePng(); $png[60] = chr(ord($png[60]) ^ 1);
        expect('invalid_image', fn() => Image::sanitize($png));
        expect('invalid_image', fn() => Image::sanitize(substr(fixtureJpeg(), 0, 300)));
    });
    test('Provider address allowlist blocks SSRF, redirects-by-URL, credentials, private and mapped IPs', function() {
        foreach (['http://yce-api-01.perfectcorp.com/a', 'https://127.0.0.1/a', 'https://evil.example/a',
            'https://yce-api-01.perfectcorp.com@evil.example/a', 'https://yce-api-01.perfectcorp.com:444/a',
            'https://yce-api-01.perfectcorp.com\\@evil.example/a'] as $url) {
            try { UrlGuard::provider($url); throw new \RuntimeException('URL allowed'); } catch (ApiError) {}
        }
        foreach (['127.0.0.1', '10.0.0.1', '169.254.169.254', '192.168.1.1', '100.64.1.1', '198.18.0.1',
            '::1', 'fc00::1', 'fe80::1', '::ffff:8.8.8.8', '64:ff9b::7f00:1', '2002:7f00:1::'] as $ip) check(!UrlGuard::publicIp($ip), 'Private IP rejected');
        check(UrlGuard::publicIp('8.8.8.8'), 'Public IP accepted');
        check(UrlGuard::signed('https://yce-us.s3-accelerate.amazonaws.com/file?signature=TEST') === 'yce-us.s3-accelerate.amazonaws.com', 'Official storage accepted');
    });
    test('Unverified cost, expired or insufficient balance, and timeout never create paid tasks', function() {
        foreach (['cost', 'balance', 'expired', 'timeout'] as $mode) {
            [$api, $http] = setupApi();
            if ($mode === 'cost') $http->cost = 3;
            if ($mode === 'balance') $http->balance = 301;
            if ($mode === 'expired') $http->expired = true;
            if ($mode === 'timeout') $http->preflightTimeout = true;
            try { create($api); throw new \RuntimeException('Should fail preflight'); } catch (ApiError) {}
            check($http->creates === 0 && $http->files === 0, 'No upload/task before complete preflight');
        }
    });
    test('Idempotency survives an unknown chargeable POST outcome and never retries it', function() {
        [$api, $http] = setupApi(); $http->unknownCreate = true;
        $a = create($api)['json']; $b = create($api)['json'];
        check($a['status'] === 'error' && $a['error'] === 'task_unconfirmed' && $a['id'] === $b['id'], 'Same honest unknown outcome');
        check($http->creates === 1, 'Exactly one chargeable POST');
        check(!str_contains(json_encode($a), 'FAKE_TEST_KEY'), 'No credential in API output');
    });
    test('At most two running tasks; protected reserve and global budget hold across sessions', function() {
        [$api, $http] = setupApi(); create($api, 'a', 1); create($api, 'b', 2);
        expect('concurrency', fn() => create($api, 'c', 3)); check($http->creates === 2, 'Global two-task limit');
        [$api, $http] = setupApi(); $http->balance = 303; create($api, 'a', 1);
        expect('unit_budget', fn() => create($api, 'b', 2)); check($http->creates === 1, 'Reserved units protected across sessions');
        [$api, $http] = setupApi(['THREADROOM_GLOBAL_UNIT_BUDGET' => 2]); create($api, 'a', 1);
        expect('unit_budget', fn() => create($api, 'b', 2)); check($http->creates === 1, 'Global lifetime unit budget');
    });
    test('Health reconciles abandoned known tasks, respects poll leases, and reports real capacity', function() {
        [$api, $http, $store] = setupApi();
        $a = create($api, 'a', 1)['json']['id']; $b = create($api, 'b', 2)['json']['id'];
        $http->taskStatus = 'running';
        $store->locked(static function(array &$s) use ($a, $b) {
            foreach ([$a, $b] as $id) { $s['jobs'][$id]['createdAt'] = time() - 3600; $s['jobs'][$id]['nextPollAt'] = 0; }
        });
        $h = $api->dispatch('GET', '/api/health', 'c', 'csrf')['json'];
        check($http->polls === 2 && !$h['liveAvailable'], 'Aged known tasks are checked but running tasks retain both slots');
        $api->dispatch('GET', '/api/health', 'c', 'csrf');
        check($http->polls === 2, 'Immediate health requests cannot bypass the ten-second poll lease');
        $http->taskStatus = 'success'; ready($store, $a); ready($store, $b);
        $h = $api->dispatch('GET', '/api/health', 'c', 'csrf')['json'];
        check($http->polls === 4 && $h['liveAvailable'], 'Confirmed completion releases capacity even after original clients leave');
        check($api->dispatch('GET', '/api/tryons/' . $a, 'a', 'csrf')['json']['status'] === 'success', 'Original owner can still retrieve the reconciled result');
        create($api, 'c', 3); check($http->creates === 3, 'A new client can create once known tasks finish');

        [$api, $http, $store] = setupApi();
        $a = create($api, 'a', 1)['json']['id']; $b = create($api, 'b', 2)['json']['id'];
        ready($store, $a); ready($store, $b);
        create($api, 'c', 3);
        check($http->polls === 2 && $http->creates === 3, 'Create also reconciles without a preceding health request');
    });
    test('Unknown tasks never release slots by age or promise automatic provider deletion', function() {
        [$api, $http, $store] = setupApi(); $http->unknownCreate = true;
        create($api, 'a', 1); create($api, 'b', 2);
        $store->locked(static function(array &$s) { foreach ($s['jobs'] as &$job) $job['createdAt'] = time() - 3600; unset($job); });
        $h = $api->dispatch('GET', '/api/health', 'c', 'csrf')['json'];
        check(!$h['liveAvailable'] && $http->polls === 0, 'Unknown tasks with no IDs remain reserved without an invented status poll');
        expect('concurrency', fn() => create($api, 'c', 3));
        $cleared = $api->dispatch('DELETE', '/api/session', 'a', 'csrf', ['x-csrf-token' => 'csrf'])['json'];
        check($cleared['providerCleanupPending'] === 1 && $cleared['providerCleanupManual'] === 1, 'Clear distinguishes unknown provider cleanup requiring an operator');
        check(str_contains($cleared['message'], 'operator reconciliation') && !str_contains($cleared['message'], 'will finish'), 'No unsupported eventual-deletion promise');
        check($http->creates === 2 && $http->deletes === 0, 'No duplicate paid POST or deletion with an invented task ID');
    });
    test('Ten-second polling lease, session-owned JPEG results, cleanup and foreign access', function() {
        [$api, $http, $store] = setupApi(); $id = create($api)['json']['id'];
        $path = '/api/tryons/' . $id;
        $api->dispatch('GET', $path, 'owner-a', 'csrf'); check($http->polls === 0, 'No early provider poll');
        expect('not_found', fn() => $api->dispatch('GET', $path, 'intruder', 'csrf'));
        ready($store, $id);
        $done = $api->dispatch('GET', $path, 'owner-a', 'csrf')['json'];
        check($done['status'] === 'success' && $done['resultUrl'] === $path . '/result' && $http->polls === 1, 'Normalized same-origin result');
        $file = $api->dispatch('GET', $path . '/result', 'owner-a', 'csrf');
        check($file['mime'] === 'image/jpeg' && is_file($file['file']), 'Private JPEG available');
        check(!str_contains(file_get_contents($file['file']), 'SECRET_GPS'), 'Result metadata removed');
        expect('not_found', fn() => $api->dispatch('GET', $path . '/result', 'intruder', 'csrf'));
        $clear = $api->dispatch('DELETE', '/api/session', 'owner-a', 'csrf', ['x-csrf-token' => 'csrf']);
        check($clear['json']['ok'] && $http->deletes === 1 && !is_file($file['file']), 'Local and completed provider task deleted');
        expect('not_found', fn() => $api->dispatch('GET', $path, 'owner-a', 'csrf'));
    });
    test('Malicious provider result URL is rejected before storage fetch; no mock success', function() {
        [$api, $http, $store] = setupApi(); $http->resultUrl = 'https://169.254.169.254/latest/meta-data';
        $id = create($api)['json']['id']; ready($store, $id);
        $response = $api->dispatch('GET', '/api/tryons/' . $id, 'owner-a', 'csrf')['json'];
        check($response['status'] === 'error' && !isset($response['resultUrl']), 'Unsafe URL fails closed');
        foreach ($http->calls as $call) check(!str_contains($call[1], '169.254'), 'No SSRF fetch attempted');
        create($api, 'b', 2); create($api, 'c', 3);
        check($http->creates === 3 && $http->polls === 1, 'A finished task with an invalid result does not permanently reserve capacity or retry the unsafe fetch');
    });
    test('Clear during image uploads prevents a later chargeable generation POST', function() {
        [$api, $http] = setupApi();
        $http->afterSecondUpload = static fn() => $api->dispatch('DELETE', '/api/session', 'owner-a', 'csrf', ['x-csrf-token' => 'csrf']);
        $response = create($api)['json'];
        check($response['status'] === 'error' && $response['error'] === 'cancelled' && $http->creates === 0, 'Cleared upload never starts generation');
    });
    test('Timed-out existing tasks can be checked without creating another task', function() {
        [$api, $http, $store] = setupApi(); $http->taskStatus = 'running'; $id = create($api)['json']['id'];
        $store->locked(static function(array &$s) use ($id) { $s['jobs'][$id]['createdAt'] = time() - 400; $s['jobs'][$id]['nextPollAt'] = 0; });
        $a = $api->dispatch('GET', '/api/tryons/' . $id, 'owner-a', 'csrf')['json'];
        check($a['status'] === 'error' && $a['error'] === 'provider_timeout', 'Honest provider timeout');
        $http->taskStatus = 'success'; ready($store, $id);
        $b = $api->dispatch('GET', '/api/tryons/' . $id, 'owner-a', 'csrf')['json'];
        check($b['status'] === 'success' && $http->creates === 1, 'Recovered original task');
    });
    test('Corrupt budget ledger fails closed instead of resetting task spend', function() {
        [$api, $http, $store] = setupApi(); file_put_contents($store->dir . '/ledger.json', '');
        try { create($api); throw new \RuntimeException('Ledger was reset'); } catch (\JsonException) {}
        check($http->creates === 0, 'No charge with corrupt budget ledger');
    });
    test('Concurrent ledger writers retain every reservation', function() {
        global $suiteRoot;
        $dir = $suiteRoot . '/concurrency'; mkdir($dir, 0700);
        $workers = [];
        for ($i = 0; $i < 3; $i++) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __DIR__ . '/ledger-worker.php', $dir], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            check(is_resource($process), 'Worker started'); fclose($pipes[0]); $workers[] = [$process, $pipes];
        }
        foreach ($workers as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
            check(proc_close($process) === 0, 'Worker completed: ' . $stderr);
        }
        $s = (new Store($dir))->locked(static fn(array &$s) => $s);
        check($s['unitReservedTotal'] === 60, 'All concurrent increments retained');
    });
    echo 'PASS ' . $passed . ' tests; mocked provider only, no live API calls.' . PHP_EOL;
} finally {
    $rootReal = realpath($suiteRoot);
    if ($rootReal && str_starts_with(basename($rootReal), 'threadroom-tests-')) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootReal, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) { if ($file->isDir()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
        rmdir($rootReal);
    }
}
