<?php
declare(strict_types=1);
namespace Threadroom;

final class YouCam
{
    public function __construct(private readonly Config $config, private readonly Transport $http) {}

    private function json(string $method, string $path, ?array $data = null): array
    {
        $url = $this->config->get('base') . $path;
        UrlGuard::provider($url);
        $body = $data === null ? '' : json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $response = $this->http->send($method, $url, [
            'Authorization: Bearer ' . $this->config->get('key'),
            'Content-Type: application/json', 'Accept: application/json',
        ], $body);
        $rejected = false;
        // Only documented task-create rejection shapes establish non-acceptance.
        // HTML, unknown errors and 5xx remain ambiguous outcomes.
        if ($method === 'POST' && $path === '/s2s/v2.0/task/cloth-v3'
            && in_array($response['status'], [400, 401, 429], true)) {
            try { $error = json_decode($response['body'], true, 64, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { $error = null; }
            if (is_array($error) && ($error['status'] ?? null) === $response['status']) {
                $rejected = $response['status'] === 400
                    ? in_array($error['error_code'] ?? null, ['InvalidParameters', 'CreditInsufficiency', 'BadRequest', 'InvalidStyleGroup', 'InvalidStyle'], true)
                    : is_string($error['error'] ?? null) && $error['error'] !== '';
            }
        }
        if ($response['status'] === 401 || $response['status'] === 403) throw new ApiError(503, 'provider_auth', 'The provider account could not be verified.', $rejected);
        if ($response['status'] === 429) throw new ApiError(503, 'provider_rate_limit', 'The provider is busy. Wait before checking again.', $rejected);
        if ($response['status'] < 200 || $response['status'] >= 300) throw new ApiError(503, 'provider_response', 'The provider could not complete this request.', $rejected);
        try { $json = json_decode($response['body'], true, 64, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new ApiError(502, 'provider_response', 'The provider returned an unreadable response.'); }
        if (!is_array($json) || ($json['status'] ?? null) !== 200) throw new ApiError(502, 'provider_response', 'The provider returned an unexpected response.');
        return $json;
    }

    /** Read-only validation: never create a task before all three facts are confirmed. */
    public function preflight(): array
    {
        if (!$this->config->configured()) throw new ApiError(503, 'not_configured', 'Live try-on is not configured yet.');
        $balanceResponse = $this->json('GET', '/s2s/v1.0/client/credit');
        if (!isset($balanceResponse['results']) || !is_array($balanceResponse['results'])) throw new ApiError(503, 'unknown_balance', 'The available API units could not be verified.');
        $balance = 0.0;
        foreach ($balanceResponse['results'] as $row) {
            if (!is_array($row) || !is_numeric($row['amount_dec'] ?? null) || !is_numeric($row['expiry'] ?? null)) continue;
            if ((float)$row['expiry'] > microtime(true) * 1000) $balance += max(0, (float)$row['amount_dec']);
        }
        $token = null; $cost = null;
        for ($page = 0; $page < 20; $page++) {
            $path = '/s2s/v2.0/credit/feature-cost?page_size=20';
            if ($token !== null) $path .= '&starting_token=' . rawurlencode($token);
            $response = $this->json('GET', $path);
            if (!is_array($response['result']['skus'] ?? null)) throw new ApiError(503, 'unknown_cost', 'The API unit cost could not be verified.');
            foreach ($response['result']['skus'] as $sku) {
                if (!is_array($sku) || !is_string($sku['run_task_url'] ?? null)) continue;
                $u = parse_url($sku['run_task_url']);
                if (($u['path'] ?? '') !== '/s2s/v2.0/task/cloth-v3') continue;
                UrlGuard::provider($sku['run_task_url']);
                if (strtolower($u['host']) !== strtolower(parse_url($this->config->get('base'), PHP_URL_HOST)) || isset($u['query'])) continue;
                if (($sku['unit'] ?? '') !== 'result_image' || (float)($sku['proc_unit'] ?? 0) !== 1.0
                    || !is_numeric($sku['amount'] ?? null) || (float)$sku['amount'] !== 2.0) {
                    throw new ApiError(503, 'unknown_cost', 'The expected cloth-v3 API and two-unit cost could not be verified.');
                }
                $cost = 2.0;
            }
            $token = $response['result']['next_token'] ?? null;
            if ($token === null) break;
            if (!is_string($token) || strlen($token) > 1024) throw new ApiError(503, 'unknown_cost', 'The API unit cost could not be verified.');
        }
        if ($cost === null) throw new ApiError(503, 'unknown_cost', 'The expected cloth-v3 API and two-unit cost could not be verified.');
        if ($balance < $this->config->get('reserve') + $cost) throw new ApiError(503, 'insufficient_units', 'Live try-on is paused to protect the remaining API units.');
        return ['balance' => $balance, 'cost' => $cost, 'checkedAt' => time()];
    }

    public function upload(array $image): string
    {
        $bytes = $image['bytes'];
        $name = $image['mime'] === 'image/jpeg' ? 'upload.jpg' : 'upload.png';
        $json = $this->json('POST', '/s2s/v2.0/file', ['files' => [[
            'content_type' => $image['mime'], 'file_name' => $name, 'file_size' => strlen($bytes),
        ]]]);
        $file = $json['data']['files'][0] ?? null;
        $request = $file['requests'][0] ?? null;
        if (!is_array($file) || !is_string($file['file_id'] ?? null) || strlen($file['file_id']) > 1024
            || !is_array($request) || ($request['method'] ?? '') !== 'PUT' || !is_string($request['url'] ?? null)) {
            throw new ApiError(502, 'provider_upload', 'The provider upload could not be prepared.');
        }
        UrlGuard::signed($request['url']);
        // The signed URL is never given the account Authorization header.
        $type = $request['headers']['Content-Type'] ?? $image['mime'];
        $length = $request['headers']['Content-Length'] ?? strlen($bytes);
        if ($type !== $image['mime'] || (int)$length !== strlen($bytes)) throw new ApiError(502, 'provider_upload', 'The provider upload metadata did not match.');
        $response = $this->http->send('PUT', $request['url'], ['Content-Type: ' . $type, 'Content-Length: ' . strlen($bytes)], $bytes, true, 100000);
        if ($response['status'] < 200 || $response['status'] >= 300) throw new ApiError(503, 'provider_upload', 'The image upload could not be confirmed.');
        return $file['file_id'];
    }

    public function create(string $personId, string $garmentId): string
    {
        $json = $this->json('POST', '/s2s/v2.0/task/cloth-v3', [
            'src_file_id' => $personId, 'ref_file_id' => $garmentId,
            'garment_category' => 'upper_body', 'change_shoes' => false,
        ]);
        $id = $json['data']['task_id'] ?? null;
        if (!is_string($id) || $id === '' || strlen($id) > 1024) throw new ApiError(502, 'provider_task', 'The task response could not be confirmed.');
        return $id;
    }

    public function poll(string $id): array
    {
        $json = $this->json('GET', '/s2s/v2.0/task/cloth-v3/' . rawurlencode($id));
        $data = $json['data'] ?? null;
        if (!is_array($data) || !in_array($data['task_status'] ?? '', ['running', 'success', 'error'], true)) {
            throw new ApiError(502, 'provider_task', 'The task status could not be verified.');
        }
        return $data;
    }

    public function result(string $url): array
    {
        UrlGuard::signed($url);
        $r = $this->http->send('GET', $url, [], '', true, Image::MAX_BYTES);
        if ($r['status'] !== 200) throw new ApiError(503, 'provider_result', 'The finished provider result could not be downloaded. No new task was submitted.');
        return Image::sanitize($r['body'], true);
    }

    public function delete(string $id): void
    {
        $this->json('POST', '/s2s/v2.0/task/delete', ['task_id' => $id]);
    }
}
