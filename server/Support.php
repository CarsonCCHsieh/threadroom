<?php
declare(strict_types=1);
namespace Threadroom;

final class ApiError extends \RuntimeException
{
    public function __construct(public readonly int $http, public readonly string $kind, string $safeMessage,
        public readonly bool $definiteProviderRejection = false)
    { parent::__construct($safeMessage); }
}

final class Config
{
    public array $values;
    public function __construct(array $overrides = [])
    {
        $root = dirname(__DIR__);
        $local = is_file($root . '/config/local.php') ? require $root . '/config/local.php' : [];
        if (!is_array($local)) throw new \RuntimeException('Invalid private configuration.');
        $read = static function(string $key, mixed $default) use ($overrides, $local): mixed {
            $env = getenv($key);
            return $overrides[$key] ?? ($env !== false && $env !== '' ? $env : ($local[$key] ?? $default));
        };
        $this->values = [
            'key' => (string)$read('YOUCAM_API_KEY', ''),
            'base' => rtrim((string)$read('YOUCAM_API_BASE_URL', 'https://yce-api-01.perfectcorp.com'), '/'),
            'live' => filter_var($read('YOUCAM_LIVE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'runtime' => (string)$read('THREADROOM_RUNTIME_DIR', $root . '/var/private'),
            'unitBudget' => (float)$read('THREADROOM_GLOBAL_UNIT_BUDGET', 300),
            'reserve' => max(300, (float)$read('THREADROOM_JUDGING_RESERVE_UNITS', 300)),
            'sessionLimit' => max(1, min(30, (int)$read('THREADROOM_SESSION_TASK_LIMIT', 12))),
            'globalLimit' => max(1, min(500, (int)$read('THREADROOM_GLOBAL_TASK_LIMIT', 150))),
            'secureCookie' => filter_var($read('THREADROOM_SECURE_COOKIE', true), FILTER_VALIDATE_BOOLEAN),
        ];
        if (preg_match('/[\r\n\x00]/', $this->values['key'])) throw new \RuntimeException('Invalid provider credential configuration.');
        if ($this->values['runtime'] === '') $this->values['runtime'] = $root . '/var/private';
        if ($this->values['unitBudget'] <= 0 || $this->values['unitBudget'] > 1000) throw new \RuntimeException('Invalid task budget.');
        UrlGuard::provider($this->values['base'] . '/');
        $dir = $this->values['runtime'];
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new \RuntimeException('Private storage unavailable.');
        $real = realpath($dir);
        $public = realpath($root . '/public') ?: $root . '/public';
        $norm = static fn(string $s): string => strtolower(str_replace('\\', '/', $s));
        if (!$real || $norm($real) === $norm($public) || str_starts_with($norm($real), $norm($public) . '/')) {
            throw new \RuntimeException('Private storage must be outside public.');
        }
        $this->values['runtime'] = $real;
        @chmod($real, 0700);
    }
    public function get(string $key): mixed { return $this->values[$key]; }
    public function configured(): bool { return $this->get('key') !== ''; }
}

final class UrlGuard
{
    public const HOSTS = ['yce-api-01.perfectcorp.com', 'yce-api-01.makeupar.com'];
    public static function provider(string $url): string
    {
        $host = self::structure($url);
        if (!in_array($host, self::HOSTS, true)) throw new ApiError(502, 'unsafe_provider', 'Provider configuration is not allowed.');
        return $host;
    }
    public static function signed(string $url): string
    {
        $host = self::structure($url);
        $allowed = preg_match('/(?:^|\.)(?:perfectcorp\.com|makeupar\.com)$/D', $host)
            || preg_match('/^[a-z0-9.-]+\.s3(?:[.-][a-z0-9-]+)*\.amazonaws\.com$/D', $host)
            || preg_match('/^s3(?:[.-][a-z0-9-]+)*\.amazonaws\.com$/D', $host);
        if (!$allowed) throw new ApiError(502, 'unsafe_provider_url', 'The provider returned an unsupported storage address.');
        return $host;
    }
    private static function structure(string $url): string
    {
        $p = parse_url($url);
        if (!$p || ($p['scheme'] ?? '') !== 'https' || !isset($p['host']) || isset($p['user']) || isset($p['pass'])
            || isset($p['fragment']) || (($p['port'] ?? 443) !== 443) || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new ApiError(502, 'unsafe_provider_url', 'The provider returned an unsafe address.');
        }
        $host = strtolower($p['host']);
        if (!preg_match('/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/D', $host) || filter_var($host, FILTER_VALIDATE_IP)) {
            throw new ApiError(502, 'unsafe_provider_url', 'The provider returned an unsafe address.');
        }
        return $host;
    }
    public static function publicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) return false;
        $packed = inet_pton($ip);
        if ($packed === false) return false;
        if (strlen($packed) === 4) {
            $number = unpack('N', $packed)[1];
            foreach ([['0.0.0.0', 8], ['100.64.0.0', 10], ['192.0.0.0', 24], ['192.0.2.0', 24],
                ['198.18.0.0', 15], ['198.51.100.0', 24], ['203.0.113.0', 24], ['224.0.0.0', 4], ['240.0.0.0', 4]] as [$network, $bits]) {
                $mask = (0xFFFFFFFF << (32 - $bits)) & 0xFFFFFFFF;
                if (($number & $mask) === (unpack('N', inet_pton($network))[1] & $mask)) return false;
            }
            return true;
        }
        // Only native global-unicast IPv6; reject mapped, NAT64, 6to4, and Teredo tunnels.
        $hex = bin2hex($packed);
        return (ord($packed[0]) & 0xE0) === 0x20 && !str_starts_with($hex, '2002')
            && !str_starts_with($hex, '20010000') && !str_starts_with($hex, '20010db8');
    }
    public static function resolve(string $host): string
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        $ips = [];
        foreach ($records ?: [] as $r) if (isset($r['ip']) || isset($r['ipv6'])) $ips[] = $r['ip'] ?? $r['ipv6'];
        if (!$ips) throw new ApiError(503, 'provider_dns', 'The provider is temporarily unreachable.');
        foreach ($ips as $ip) if (!self::publicIp($ip)) throw new ApiError(502, 'unsafe_provider_url', 'The provider resolved to an unsafe address.');
        usort($ips, static fn($a, $b) => (int)str_contains($a, ':') <=> (int)str_contains($b, ':'));
        return $ips[0];
    }
}

interface Transport
{
    /** @return array{status:int,body:string} */
    public function send(string $method, string $url, array $headers = [], string $body = '', bool $signed = false, int $maxBytes = 1000000): array;
}

final class CurlTransport implements Transport
{
    public function send(string $method, string $url, array $headers = [], string $body = '', bool $signed = false, int $maxBytes = 1000000): array
    {
        $host = $signed ? UrlGuard::signed($url) : UrlGuard::provider($url);
        $ip = UrlGuard::resolve($host);
        if (!extension_loaded('curl')) throw new ApiError(503, 'provider_unavailable', 'The provider connection is unavailable.');
        $response = ''; $tooLarge = false;
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 35, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROXY => '',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RESOLVE => [$host . ':443:' . (str_contains($ip, ':') ? '[' . $ip . ']' : $ip)],
            CURLOPT_WRITEFUNCTION => static function($handle, string $chunk) use (&$response, &$tooLarge, $maxBytes): int {
                if (strlen($response) + strlen($chunk) > $maxBytes) { $tooLarge = true; return 0; }
                $response .= $chunk; return strlen($chunk);
            },
        ]);
        if ($method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($ok === false || $tooLarge) throw new ApiError(503, 'provider_connection', 'The provider response could not be confirmed.');
        return ['status' => $status, 'body' => $response];
    }
}

final class Store
{
    public function __construct(public readonly string $dir) {}
    public function locked(callable $fn): mixed
    {
        $fp = fopen($this->dir . '/ledger.lock', 'c+');
        if (!$fp || !flock($fp, LOCK_EX)) throw new \RuntimeException('Private storage unavailable.');
        @chmod($this->dir . '/ledger.lock', 0600);
        try {
            $ledger = $this->dir . '/ledger.json';
            $state = !is_file($ledger) ? ['jobs' => [], 'unitReservedTotal' => 0]
                : json_decode((string)file_get_contents($ledger), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($state) || !isset($state['jobs'], $state['unitReservedTotal'])) throw new \RuntimeException('Invalid private ledger.');
            $result = $fn($state);
            $json = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $temp = $this->dir . '/ledger-' . bin2hex(random_bytes(12)) . '.tmp';
            if (file_put_contents($temp, $json, LOCK_EX) !== strlen($json)) { @unlink($temp); throw new \RuntimeException('Private storage unavailable.'); }
            @chmod($temp, 0600);
            if (!rename($temp, $ledger)) { @unlink($temp); throw new \RuntimeException('Private storage unavailable.'); }
            return $result;
        } finally { flock($fp, LOCK_UN); fclose($fp); }
    }
    public function privateFile(string $name): string
    {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png)$/D', $name)) throw new \RuntimeException('Invalid private filename.');
        return $this->dir . '/' . $name;
    }
    public function writeImage(array $image): string
    {
        $name = bin2hex(random_bytes(16)) . ($image['mime'] === 'image/jpeg' ? '.jpg' : '.png');
        $path = $this->privateFile($name);
        if (file_put_contents($path, $image['bytes'], LOCK_EX) !== strlen($image['bytes'])) throw new \RuntimeException('Private storage unavailable.');
        @chmod($path, 0600);
        return $name;
    }
}
