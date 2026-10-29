<?php
declare(strict_types=1);
namespace Threadroom;

final class Api
{
    private const RESULT_ATTEMPT_LIMIT = 3;
    private const RESULT_RECOVERY_SECONDS = 300;
    private Store $store;
    private YouCam $provider;
    public function __construct(private readonly Config $config, ?Transport $transport = null)
    {
        $this->store = new Store($config->get('runtime'));
        $this->provider = new YouCam($config, $transport ?? new CurlTransport());
    }

    public function dispatch(string $method, string $path, string $owner, string $csrf, array $headers = [], array $form = [], array $uploads = []): array
    {
        if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) throw new ApiError(405, 'method_not_allowed', 'This method is not supported.');
        if ($method !== 'GET' && (!isset($headers['x-csrf-token']) || !is_string($headers['x-csrf-token']) || !hash_equals($csrf, $headers['x-csrf-token']))) {
            throw new ApiError(403, 'csrf', 'Refresh the page before trying again.');
        }
        if ($method === 'GET' && $path === '/api/health') return ['http' => 200, 'json' => $this->health($owner, $csrf)];
        if ($method === 'POST' && $path === '/api/tryons') return $this->create($owner, $form, $uploads);
        if ($method === 'DELETE' && $path === '/api/session') return ['http' => 200, 'json' => $this->clear($owner, $csrf)];
        if ($method === 'GET' && preg_match('#^/api/tryons/([a-f0-9]{32})(/result)?$#D', $path, $m)) {
            $job = $this->owned($m[1], $owner);
            if (isset($m[2])) {
                if ($job['status'] !== 'success' || !isset($job['resultFile'])) throw new ApiError(404, 'result_unavailable', 'This result is not available.');
                $file = $this->store->privateFile($job['resultFile']);
                if (!is_file($file)) throw new ApiError(410, 'result_expired', 'This private result is no longer available.');
                return ['http' => 200, 'file' => $file, 'mime' => 'image/jpeg'];
            }
            return ['http' => 200, 'json' => $this->normalized($this->poll($job['id']))];
        }
        throw new ApiError(404, 'not_found', 'This resource was not found.');
    }

    private function health(string $owner, string $csrf): array
    {
        $result = ['configured' => $this->config->configured(), 'provider' => 'YouCam', 'liveAvailable' => false,
            'message' => 'Live try-on is not configured yet. You can explore the sample wardrobe.', 'csrf' => $csrf];
        if (!$result['configured']) return $result;
        try {
            $this->reconcile();
            $cached = $this->store->locked(static fn(array &$s) => $s['preflight'] ?? null);
            if (!is_array($cached) || ($cached['checkedAt'] ?? 0) < time() - 30) {
                $cached = $this->provider->preflight();
                $this->store->locked(static function(array &$s) use ($cached) { $s['preflight'] = $cached; });
            }
            $capacity = $this->store->locked(function(array &$s) use ($owner, $cached) {
                $active = 0; $pendingCost = 0; $ownerJobs = 0; $recent = 0;
                foreach ($s['jobs'] as $job) {
                    if ($this->active($job)) { $active++; $pendingCost += $job['cost']; }
                    if ($job['owner'] === $owner) {
                        $ownerJobs++;
                        if ($job['createdAt'] > time() - 60) $recent++;
                    }
                }
                if ($active >= 2) return 'Live try-on is paused while two provider tasks are active or awaiting reconciliation.';
                if ($ownerJobs >= $this->config->get('sessionLimit') || count($s['jobs']) >= $this->config->get('globalLimit')) {
                    return 'The try-on task limit has been reached.';
                }
                if ($recent >= 3) return 'Wait a minute before starting another try-on.';
                if ($s['unitReservedTotal'] + $cached['cost'] > $this->config->get('unitBudget')
                    || $cached['balance'] - $pendingCost - $cached['cost'] < $this->config->get('reserve')) {
                    return 'Live try-on is paused to protect the API budget.';
                }
                return null;
            });
            $result['liveAvailable'] = $this->config->get('live') && $capacity === null;
            $result['message'] = !$this->config->get('live') ? 'The provider is verified. Live try-on has not been enabled.'
                : ($capacity ?? 'Live virtual try-on is ready. Your photo is sent only after you consent.');
        } catch (ApiError $e) { $result['message'] = $e->getMessage(); }
        return $result;
    }

    private function create(string $owner, array $form, array $uploads): array
    {
        if (($form['consent'] ?? '') !== 'true' || ($form['category'] ?? '') !== 'upper_body') {
            throw new ApiError(422, 'invalid_request', 'Consent and the upper-body category are required.');
        }
        $key = $form['idempotency_key'] ?? '';
        if (!is_string($key) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/iD', $key)) {
            throw new ApiError(422, 'invalid_idempotency_key', 'A valid request identifier is required.');
        }
        if (!is_string($uploads['person'] ?? null) || !is_string($uploads['garment'] ?? null)) throw new ApiError(422, 'missing_image', 'Choose a person photo and a garment photo.');
        $person = Image::sanitize($uploads['person']); $garment = Image::sanitize($uploads['garment']);
        $fingerprint = hash('sha256', $person['bytes'] . "\0" . $garment['bytes'] . "\0upper_body");
        $key = strtolower($key);
        $existing = $this->store->locked(static function(array &$s) use ($owner, $key, $fingerprint) {
            foreach ($s['jobs'] as $job) if ($job['owner'] === $owner && $job['idempotencyKey'] === $key) {
                if ($job['fingerprint'] !== $fingerprint || ($job['deleted'] ?? false)) throw new ApiError(409, 'idempotency_conflict', 'This request identifier has already been used.');
                return $job;
            }
            return null;
        });
        if ($existing) return ['http' => 200, 'json' => $this->normalized($existing)];
        if (!$this->config->configured() || !$this->config->get('live')) throw new ApiError(503, 'live_unavailable', 'Live try-on is not enabled. No API units have been used.');
        $this->reconcile();
        $preflight = $this->provider->preflight(); // Fresh, read-only account/cost/balance verification.
        $reservation = $this->store->locked(function(array &$s) use ($owner, $key, $fingerprint, $preflight) {
            $ownerJobs = 0; $active = 0; $pendingCost = 0; $recent = 0;
            foreach ($s['jobs'] as $job) {
                if ($job['owner'] === $owner) {
                    $ownerJobs++;
                    if ($job['idempotencyKey'] === $key) {
                        if ($job['fingerprint'] !== $fingerprint || ($job['deleted'] ?? false)) throw new ApiError(409, 'idempotency_conflict', 'This request identifier has already been used.');
                        return ['new' => false, 'job' => $job];
                    }
                    if ($job['createdAt'] > time() - 60) $recent++;
                }
                if ($this->active($job)) { $active++; $pendingCost += $job['cost']; }
            }
            if ($recent >= 3) throw new ApiError(429, 'rate_limit', 'Wait a minute before starting another try-on.');
            if ($ownerJobs >= $this->config->get('sessionLimit') || count($s['jobs']) >= $this->config->get('globalLimit')) throw new ApiError(429, 'task_budget', 'The try-on task limit has been reached.');
            if ($active >= 2) throw new ApiError(429, 'concurrency', 'Two try-ons are already running. Wait for one to finish.');
            if ($s['unitReservedTotal'] + $preflight['cost'] > $this->config->get('unitBudget')
                || $preflight['balance'] - $pendingCost - $preflight['cost'] < $this->config->get('reserve')) {
                throw new ApiError(503, 'unit_budget', 'Live try-on is paused to protect the remaining API units.');
            }
            $job = ['id' => bin2hex(random_bytes(16)), 'owner' => $owner, 'idempotencyKey' => $key,
                'fingerprint' => $fingerprint, 'status' => 'queued', 'providerStatus' => 'not_started',
                'createdAt' => time(), 'nextPollAt' => 0, 'cost' => $preflight['cost'],
                'message' => 'Preparing your private try-on.', 'deleted' => false];
            $s['jobs'][$job['id']] = $job; $s['unitReservedTotal'] += $job['cost'];
            return ['new' => true, 'job' => $job];
        });
        if (!$reservation['new']) return ['http' => 200, 'json' => $this->normalized($reservation['job'])];
        $job = $reservation['job']; $taskMayExist = false;
        try {
            $personId = $this->provider->upload($person);
            $garmentId = $this->provider->upload($garment);
            $this->store->locked(static function(array &$s) use ($job) {
                if ($s['jobs'][$job['id']]['deleted']) throw new ApiError(409, 'cancelled', 'This try-on was cleared before generation started.');
                $s['jobs'][$job['id']]['providerStatus'] = 'creating';
            });
            $taskMayExist = true; // Set BEFORE the potentially chargeable POST.
            $taskId = $this->provider->create($personId, $garmentId);
            $job = $this->update($job['id'], ['status' => 'running', 'providerStatus' => 'running', 'taskId' => $taskId,
                'nextPollAt' => time() + 10, 'message' => 'YouCam is creating your try-on.']);
        } catch (ApiError $e) {
            $unknown = $taskMayExist && !$e->definiteProviderRejection;
            $job = $this->update($job['id'], ['status' => 'error', 'providerStatus' => $unknown ? 'unknown' : 'not_started',
                'errorCode' => $unknown ? 'task_unconfirmed' : $e->kind,
                'message' => $unknown ? 'The provider could not confirm this task. It will not be submitted again automatically.' : $e->getMessage()]);
        } catch (\Throwable) {
            $job = $this->update($job['id'], ['status' => 'error', 'providerStatus' => $taskMayExist ? 'unknown' : 'not_started',
                'errorCode' => 'task_unconfirmed', 'message' => 'The task could not be confirmed. It will not be submitted again automatically.']);
        }
        // Only sanitized bytes existed in request memory. Neither upload is written to disk.
        return ['http' => 202, 'json' => $this->normalized($job)];
    }

    private function active(array $job): bool
    {
        return in_array($job['providerStatus'], ['not_started', 'creating', 'running', 'unknown'], true)
            && in_array($job['status'], ['queued', 'running', 'error'], true)
            && ($job['providerStatus'] !== 'not_started' || $job['createdAt'] > time() - 900)
            && !($job['providerStatus'] === 'not_started' && $job['status'] === 'error');
    }

    private function owned(string $id, string $owner): array
    {
        return $this->store->locked(static function(array &$s) use ($id, $owner) {
            $job = $s['jobs'][$id] ?? null;
            if (!$job || !hash_equals($job['owner'], $owner) || ($job['deleted'] ?? false)) throw new ApiError(404, 'not_found', 'This private try-on was not found.');
            return $job;
        });
    }

    private function update(string $id, array $changes, ?string $pollToken = null): array
    {
        return $this->store->locked(static function(array &$s) use ($id, $changes, $pollToken) {
            if (!isset($s['jobs'][$id])) throw new \RuntimeException('Task state unavailable.');
            if ($pollToken !== null && ($s['jobs'][$id]['pollToken'] ?? null) !== $pollToken) return $s['jobs'][$id];
            $s['jobs'][$id] = array_replace($s['jobs'][$id], $changes);
            return $s['jobs'][$id];
        });
    }

    private function poll(string $id): array
    {
        $pollToken = bin2hex(random_bytes(16));
        $lease = $this->store->locked(static function(array &$s) use ($id, $pollToken) {
            $job = $s['jobs'][$id];
            $recover = $job['providerStatus'] === 'success' && ($job['resultPending'] ?? false) && !$job['deleted'];
            if (!isset($job['taskId']) || (in_array($job['providerStatus'], ['success', 'error'], true) && !$recover)
                || ($job['pollLeaseUntil'] ?? 0) > time()) return ['poll' => false, 'job' => $job];
            if ($recover && (($job['resultAttempts'] ?? 0) >= self::RESULT_ATTEMPT_LIMIT || ($job['resultRetryUntil'] ?? 0) <= time())) {
                $s['jobs'][$id] = array_replace($job, ['resultPending' => false, 'status' => 'error', 'errorCode' => 'result_recovery_exhausted',
                    'message' => 'This completed task could not be downloaded within the recovery limit. No new task was submitted.']);
                return ['poll' => false, 'job' => $s['jobs'][$id]];
            }
            if ($job['nextPollAt'] > time()) return ['poll' => false, 'job' => $job];
            $s['jobs'][$id]['nextPollAt'] = time() + 10;
            // Two sequential transport calls can each take 35 seconds. This
            // in-flight lease keeps concurrent workers within the attempt limit.
            $s['jobs'][$id]['pollToken'] = $pollToken;
            $s['jobs'][$id]['pollLeaseUntil'] = time() + 80;
            if ($recover) $s['jobs'][$id]['resultAttempts']++;
            return ['poll' => true, 'job' => $s['jobs'][$id]];
        });
        $job = $lease['job'];
        if (!$lease['poll']) return $job;
        $finishedStatus = null;
        try {
            $data = $this->provider->poll($job['taskId']);
            if ($data['task_status'] === 'running') {
                $changes = ['status' => 'running', 'message' => 'YouCam is still creating your try-on.'];
                if ($job['createdAt'] < time() - 300) $changes = ['status' => 'error', 'errorCode' => 'provider_timeout',
                    'message' => 'The provider is taking longer than expected. Check again; this task will not be resubmitted.'];
                return $this->update($id, $changes, $pollToken);
            }
            $finishedStatus = $data['task_status'];
            if ($data['task_status'] === 'error') {
                $job = $this->update($id, ['status' => 'error', 'providerStatus' => 'error', 'errorCode' => 'generation_failed',
                    'resultPending' => false, 'message' => 'The provider could not process these photos. Try clear, front-facing images with one person.'], $pollToken);
            } else {
                $job = $this->update($id, ['providerStatus' => 'success', 'resultPending' => true,
                    'resultAttempts' => max(1, $job['resultAttempts'] ?? 0),
                    'resultRetryUntil' => $job['resultRetryUntil'] ?? time() + self::RESULT_RECOVERY_SECONDS], $pollToken);
                if (($job['pollToken'] ?? null) !== $pollToken) return $job;
                $deleted = $this->store->locked(static fn(array &$s) => $s['jobs'][$id]['deleted']);
                if ($deleted) {
                    $job = $this->update($id, ['providerStatus' => 'success', 'resultPending' => false], $pollToken);
                } else {
                    $url = $data['results']['url'] ?? null;
                    if (!is_string($url)) throw new ApiError(502, 'provider_result', 'The provider result could not be verified.');
                    $image = $this->provider->result($url);
                    $filename = $this->store->writeImage($image);
                    $job = $this->store->locked(function(array &$s) use ($id, $filename, $pollToken) {
                        if (($s['jobs'][$id]['pollToken'] ?? null) !== $pollToken) {
                            @unlink($this->store->privateFile($filename));
                            return $s['jobs'][$id];
                        }
                        if ($s['jobs'][$id]['deleted']) {
                            @unlink($this->store->privateFile($filename));
                            $s['jobs'][$id]['providerStatus'] = 'success';
                            $s['jobs'][$id]['resultPending'] = false;
                        } else {
                            $s['jobs'][$id] = array_replace($s['jobs'][$id], ['status' => 'success', 'providerStatus' => 'success',
                                'resultPending' => false, 'resultFile' => $filename, 'message' => 'Your private try-on is ready.']);
                        }
                        return $s['jobs'][$id];
                    });
                }
            }
            if ($job['deleted']) $this->deleteFinished($job);
            return $job;
        } catch (ApiError $e) {
            if ($finishedStatus === 'success' || $job['providerStatus'] === 'success') {
                return $this->store->locked(static function(array &$s) use ($id, $pollToken, $e) {
                    $current = &$s['jobs'][$id];
                    if (($current['pollToken'] ?? null) !== $pollToken) return $current;
                    $retryable = in_array($e->kind, ['provider_connection', 'provider_dns', 'provider_rate_limit'], true)
                        || (in_array($e->kind, ['provider_result', 'provider_response'], true) && $e->http === 503);
                    $retry = $retryable && !$current['deleted'] && ($current['resultAttempts'] ?? 0) < self::RESULT_ATTEMPT_LIMIT
                        && ($current['resultRetryUntil'] ?? 0) > time();
                    $current = array_replace($current, ['providerStatus' => 'success', 'resultPending' => $retry,
                        'status' => $retry ? 'running' : 'error', 'errorCode' => $retryable && !$retry ? 'result_recovery_exhausted' : $e->kind,
                        'message' => $retry ? 'Your task completed. The result download will be checked again without starting a new task.'
                            : ($retryable ? 'This completed task could not be downloaded within the recovery limit. No new task was submitted.' : $e->getMessage())]);
                    if ($retry) $current['nextPollAt'] = time() + 10 * (2 ** max(0, $current['resultAttempts'] - 1));
                    return $current;
                });
            }
            $changes = ['status' => 'error', 'errorCode' => $e->kind, 'message' => $e->getMessage()];
            // Without confirmed completion, preserve the original task for
            // later reconciliation instead of creating another paid task.
            if ($finishedStatus !== null) $changes['providerStatus'] = $finishedStatus;
            return $this->update($id, $changes, $pollToken);
        } finally {
            $this->store->locked(static function(array &$s) use ($id, $pollToken) {
                if (($s['jobs'][$id]['pollToken'] ?? null) === $pollToken) unset($s['jobs'][$id]['pollToken'], $s['jobs'][$id]['pollLeaseUntil']);
            });
        }
    }

    private function normalized(array $job): array
    {
        if ($job['deleted']) return ['id' => $job['id'], 'status' => 'error', 'error' => 'cancelled', 'message' => 'This private try-on was cleared.'];
        $r = ['id' => $job['id'], 'status' => $job['status'], 'message' => $job['message']];
        if ($job['status'] === 'success' && isset($job['resultFile'])) $r['resultUrl'] = '/api/tryons/' . $job['id'] . '/result';
        if ($job['status'] === 'error') $r['error'] = $job['errorCode'] ?? 'generation_failed';
        return $r;
    }

    private function deleteFinished(array $job): bool
    {
        if (!isset($job['taskId']) || !in_array($job['providerStatus'], ['success', 'error'], true)) return false;
        $allowed = $this->store->locked(static function(array &$s) use ($job) {
            $current = &$s['jobs'][$job['id']];
            if (($current['providerDeleted'] ?? false)) return false;
            if (($current['nextDeleteAt'] ?? 0) > time()) return false;
            $current['nextDeleteAt'] = time() + 10;
            return true;
        });
        if (!$allowed) return false;
        try { $this->provider->delete($job['taskId']); $this->update($job['id'], ['providerDeleted' => true]); return true; }
        catch (ApiError) { return false; }
    }

    private function reconcile(): void
    {
        // A returning client must not be necessary to release a known finished task.
        // Bound network work per request; poll() enforces scheduling and a shared in-flight lease.
        $jobs = $this->store->locked(static function(array &$s) {
            $due = array_values(array_filter($s['jobs'], static fn(array $j) => isset($j['taskId'])
                && (!in_array($j['providerStatus'], ['success', 'error'], true)
                    || ($j['providerStatus'] === 'success' && ($j['resultPending'] ?? false) && !$j['deleted'])) && $j['nextPollAt'] <= time()));
            usort($due, static fn(array $a, array $b) => $a['nextPollAt'] <=> $b['nextPollAt']);
            return array_slice($due, 0, 2);
        });
        foreach ($jobs as $job) $this->poll($job['id']);
        $finished = $this->store->locked(static fn(array &$s) => array_slice(array_values(array_filter($s['jobs'],
            static fn(array $j) => $j['deleted'] && isset($j['taskId']) && !($j['providerDeleted'] ?? false)
                && in_array($j['providerStatus'], ['success', 'error'], true) && ($j['nextDeleteAt'] ?? 0) <= time())), 0, 2));
        foreach ($finished as $job) $this->deleteFinished($job);
    }

    private function clear(string $owner, string $csrf): array
    {
        $jobs = $this->store->locked(function(array &$s) use ($owner) {
            $jobs = [];
            foreach ($s['jobs'] as $id => &$job) if ($job['owner'] === $owner) {
                if (isset($job['resultFile'])) @unlink($this->store->privateFile($job['resultFile']));
                unset($job['resultFile']); $job['deleted'] = true; $job['resultPending'] = false; $jobs[] = $job;
            }
            unset($job);
            return $jobs;
        });
        $pending = 0; $manual = 0;
        foreach ($jobs as $job) {
            if (($job['providerDeleted'] ?? false)) continue;
            if (!isset($job['taskId'])) {
                if (in_array($job['providerStatus'], ['creating', 'unknown'], true)) { $pending++; $manual++; }
                continue;
            }
            if (!$this->deleteFinished($job)) $pending++;
        }
        return ['ok' => true, 'csrf' => $csrf, 'providerCleanupPending' => $pending, 'providerCleanupManual' => $manual,
            'message' => $manual ? 'Private local results were cleared. Unconfirmed provider tasks require operator reconciliation; automatic deletion is unavailable without a task ID. Provider retention may still apply.'
                : ($pending ? 'Private local results were cleared. Provider cleanup is pending; later health or try-on requests can check known tasks and retry deletion. Provider retention may still apply.'
                    : 'Private results were cleared.')];
    }
}
