<?php

namespace App\Services\Chat;

use App\Models\Widget;
use App\Models\WidgetApiLookup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Eksekusi API Lookup: chatbot mencari data dari API eksternal saat
 * percakapan berlangsung.
 *
 * AI memicu via blok JSON:
 *   {"action": "api_lookup", "lookup": "<key>", "params": {"p": "..."}}
 *
 * Metode statis di bawah ini murni (tanpa facade) agar bisa diuji
 * tanpa bootstrap Laravel.
 */
class ApiLookupService
{
    public const MAX_RESPONSE_BYTES = 262144; // 256 KB
    public const RATE_LIMIT_PER_MINUTE = 30;
    public const MAX_REDIRECTS = 2;
    public const MAX_FIELD_CHARS = 500;

    /**
     * Entry point dari ChatOrchestrator. SELALU mengembalikan string
     * balasan untuk pengunjung (tidak pernah null) agar alur tidak jatuh
     * ke generic webhook flow.
     */
    public function dispatch(Widget $widget, array $action): string
    {
        $key = trim((string) ($action['lookup'] ?? ''));
        $params = $action['params'] ?? [];
        if (! is_array($params)) {
            $params = [];
        }

        /** @var WidgetApiLookup|null $lookup */
        $lookup = $widget->apiLookups()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if (! $lookup) {
            Log::warning('api_lookup: lookup tidak dikenal / nonaktif', [
                'widget_id' => $widget->id,
                'key' => $key,
            ]);

            return 'Maaf, layanan pencarian data sedang tidak tersedia.';
        }

        $rateKey = 'api-lookup:'.$widget->id;
        if (RateLimiter::tooManyAttempts($rateKey, self::RATE_LIMIT_PER_MINUTE)) {
            return 'Terlalu banyak permintaan pencarian. Silakan coba lagi sebentar.';
        }
        RateLimiter::hit($rateKey, 60);

        $cacheKey = null;
        if ($lookup->cache_ttl_seconds > 0) {
            $cacheKey = 'api-lookup-result:'.$lookup->id.':'.md5((string) json_encode($params));
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $result = $this->run($lookup, $params);

        if (! $result['ok']) {
            Log::warning('api_lookup gagal', [
                'widget_id' => $widget->id,
                'lookup' => $lookup->key,
                'http_status' => $result['http_status'],
                'error' => $result['error'],
                'url' => self::safeLogUrl($result['url']),
                'duration_ms' => $result['duration_ms'],
            ]);

            return $lookup->error_message
                ?: 'Maaf, layanan pencarian data sedang tidak dapat dihubungi. Silakan coba lagi nanti.';
        }

        if ($cacheKey !== null) {
            Cache::put($cacheKey, $result['reply'], (int) $lookup->cache_ttl_seconds);
        }

        return $result['reply'];
    }

    /**
     * Eksekusi inti. Dipakai dispatch() dan tombol "Uji" di UI.
     *
     * @return array{ok:bool,http_status:?int,mapped:array,reply:string,error:?string,url:string,duration_ms:int}
     */
    public function run(WidgetApiLookup $lookup, array $params): array
    {
        $started = microtime(true);
        $url = '';

        try {
            // 1. Parameter wajib
            foreach ($lookup->params ?? [] as $def) {
                $name = $def['name'] ?? null;
                if (! $name) {
                    continue;
                }
                $value = trim((string) ($params[$name] ?? ''));
                if (! empty($def['required']) && $value === '') {
                    return $this->fail($url, 'missing_param', 'Parameter wajib "'.$name.'" belum diberikan.', $started);
                }
                $params[$name] = $value;
            }

            // 2. Bangun URL dari template
            $url = self::substituteUrlTemplate(
                (string) $lookup->url_template,
                $params,
                $lookup->params ?? []
            );
            self::assertUrlSafe($url);

            // 3. Header + auth
            $headers = [];
            foreach ($lookup->headers ?? [] as $h) {
                if (! empty($h['name'])) {
                    $headers[$h['name']] = (string) ($h['value'] ?? '');
                }
            }
            $this->applyAuth($lookup, $headers);
            $url = $this->appendQuery($url, $this->pendingQuery);
            $this->pendingQuery = [];

            // Validasi ulang URL final (termasuk query auth) sebelum request.
            self::assertUrlSafe($url);

            // 4. Request (redirect ditangani manual agar tetap SSRF-aman)
            $response = $this->getWithRedirects($url, $headers, max(3, (int) $lookup->timeout_seconds));
            $status = $response['status'];
            $body = $response['body'];

            if ($status < 200 || $status >= 300) {
                return $this->fail($url, 'http_error', 'API mengembalikan HTTP '.$status.'.', $started, $status);
            }
            if (strlen($body) > self::MAX_RESPONSE_BYTES) {
                return $this->fail($url, 'too_large', 'Respons API terlalu besar.', $started, $status);
            }
            $json = json_decode($body, true);
            if (! is_array($json)) {
                return $this->fail($url, 'bad_json', 'Respons API bukan JSON.', $started, $status);
            }

            // 5. Deteksi hasil kosong
            if ($lookup->empty_path) {
                $check = self::arrayGet($json, (string) $lookup->empty_path);
                if (empty($check)) {
                    return $this->ok($url, $status, [], $lookup->empty_message ?: 'Data tidak ditemukan.', $started);
                }
            }

            // 6. Mapping respons -> field
            $mapped = self::mapResponse($json, $lookup->response_mapping ?? []);

            // 7. Render template balasan
            $reply = self::renderTemplate($lookup->reply_template, $mapped);
            if (trim($reply) === '') {
                $lines = [];
                foreach ($mapped as $k => $v) {
                    if ($v !== '') {
                        $lines[] = $k.': '.$v;
                    }
                }
                $reply = implode("\n", $lines)
                    ?: ($lookup->empty_message ?: 'Data ditemukan.');
            }

            return $this->ok($url, $status, $mapped, $reply, $started);
        } catch (\Throwable $e) {
            return $this->fail($url, 'exception', $e->getMessage(), $started);
        }
    }

    /**
     * Versi untuk tombol "Uji" di UI: kembalikan struktur detail
     * (URL aman-log, status, mapping, balasan) sebagai JSON.
     */
    public function executeForTest(WidgetApiLookup $lookup, array $params): array
    {
        $result = $this->run($lookup, $params);

        return [
            'ok' => $result['ok'],
            'http_status' => $result['http_status'],
            'url' => self::safeLogUrl($result['url']),
            'mapped' => $result['mapped'],
            'reply' => $result['reply'],
            'error' => $result['error'],
            'duration_ms' => $result['duration_ms'],
        ];
    }

    /** @var array Query tambahan (auth api_key_query), dikosongkan tiap run. */
    protected array $pendingQuery = [];

    protected function applyAuth(WidgetApiLookup $lookup, array &$headers): void
    {
        switch ($lookup->auth_type) {
            case 'bearer':
                $headers['Authorization'] = 'Bearer '.(string) $lookup->auth_secret;
                break;
            case 'api_key_header':
                $name = $lookup->auth_config['header_name'] ?? 'X-API-Key';
                $headers[$name] = (string) $lookup->auth_secret;
                break;
            case 'api_key_query':
                $param = $lookup->auth_config['param_name'] ?? 'api_key';
                $this->pendingQuery[$param] = (string) $lookup->auth_secret;
                break;
            case 'none':
            default:
                break;
        }
    }

    protected function appendQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }

    /**
     * GET dengan penanganan redirect manual: setiap hop divalidasi
     * SSRF sebelum diikuti.
     *
     * @return array{status:int,body:string}
     */
    protected function getWithRedirects(string $url, array $headers, int $timeout): array
    {
        $current = $url;

        for ($i = 0; $i <= self::MAX_REDIRECTS; $i++) {
            self::assertUrlSafe($current);

            $resp = Http::withHeaders($headers)
                ->timeout($timeout)
                ->connectTimeout(min(5, $timeout))
                ->withoutRedirecting()
                ->withUserAgent('Cekat-ApiLookup/1.0')
                ->get($current);

            $status = $resp->status();

            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                $location = $resp->header('Location');
                if (! $location) {
                    break;
                }
                $current = self::resolveUrl($current, $location);
                continue;
            }

            return ['status' => $status, 'body' => (string) $resp->body()];
        }

        throw new \RuntimeException('Terlalu banyak redirect.');
    }

    protected function ok(string $url, ?int $status, array $mapped, string $reply, float $started): array
    {
        return [
            'ok' => true,
            'http_status' => $status,
            'mapped' => $mapped,
            'reply' => $reply,
            'error' => null,
            'url' => $url,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    protected function fail(string $url, string $code, string $message, float $started, ?int $status = null): array
    {
        Log::debug('api_lookup run gagal', ['code' => $code, 'message' => $message]);

        return [
            'ok' => false,
            'http_status' => $status,
            'mapped' => [],
            'reply' => '',
            'error' => $message,
            'url' => $url,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    // ------------------------------------------------------------------
    // Metode statis murni (tanpa facade) — dapat diuji tanpa Laravel.
    // ------------------------------------------------------------------

    /**
     * Substitusi placeholder {param} di template URL dengan nilai
     * ter-URL-encode. Placeholder harus terdefinisi di $paramDefs.
     *
     * @param array $params nilai dari AI/user, keyed by name
     * @param array $paramDefs definisi parameter [{name, ...}]
     */
    public static function substituteUrlTemplate(string $template, array $params, array $paramDefs): string
    {
        $defined = [];
        foreach ($paramDefs as $def) {
            if (! empty($def['name'])) {
                $defined[$def['name']] = true;
            }
        }

        return (string) preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function ($m) use ($params, $defined) {
                $name = $m[1];
                if (! isset($defined[$name])) {
                    throw new \RuntimeException('Placeholder "{'.$name.'}" tidak terdefinisi di parameter lookup.');
                }

                return rawurlencode((string) ($params[$name] ?? ''));
            },
            $template
        );
    }

    /**
     * Ambil nilai dari array dengan dot-path. Mendukung indeks
     * array gaya data[0].field maupun data.0.field.
     */
    public static function arrayGet(array $data, string $path)
    {
        $path = preg_replace('/\[(\d+)\]/', '.$1', $path);
        $current = $data;

        foreach (explode('.', (string) $path) as $segment) {
            if ($segment === '') {
                continue;
            }
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Petakan respons JSON ke field datar sesuai $mapping {field: path}.
     */
    public static function mapResponse(array $json, array $mapping): array
    {
        $out = [];

        foreach ($mapping as $field => $path) {
            if (! is_string($field) || $field === '' || ! is_string($path) || $path === '') {
                continue;
            }
            $value = self::arrayGet($json, $path);

            if (is_array($value)) {
                $value = implode(', ', array_map(
                    fn ($v) => is_scalar($v) ? (string) $v : (string) json_encode($v),
                    $value
                ));
            } elseif (is_bool($value)) {
                $value = $value ? 'ya' : 'tidak';
            } elseif ($value === null) {
                $value = '';
            } else {
                $value = (string) $value;
            }

            $out[$field] = mb_substr(trim($value), 0, self::MAX_FIELD_CHARS);
        }

        return $out;
    }

    /**
     * Render template balasan: {field} diganti nilai mapping.
     */
    public static function renderTemplate(?string $template, array $mapped): string
    {
        if (! $template) {
            return '';
        }

        return (string) preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            fn ($m) => $mapped[$m[1]] ?? '',
            $template
        );
    }

    /**
     * Selesaikan URL redirect relatif terhadap URL dasar.
     */
    public static function resolveUrl(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }
        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $path = $parts['path'] ?? '/';
        $dir = rtrim((string) substr($path, 0, (int) strrpos($path, '/') + 1), '/');

        return "{$scheme}://{$host}{$port}{$dir}/{$location}";
    }

    /**
     * Penjaga SSRF: tolak skema non-http(s), kredensial di URL,
     * host tak ter-resolve, dan IP privat/reserved/loopback.
     * DNS di-resolve ulang SETIAP request (bukan hanya saat simpan).
     *
     * @throws \RuntimeException bila URL tidak aman
     */
    public static function assertUrlSafe(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false) {
            throw new \RuntimeException('URL tidak valid.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Skema URL tidak diizinkan.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new \RuntimeException('URL tidak boleh mengandung kredensial.');
        }

        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            throw new \RuntimeException('Host URL kosong.');
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $records = dns_get_record($host, DNS_A + DNS_AAAA);
            if ($records === false || $records === []) {
                throw new \RuntimeException('Host tidak dapat di-resolve: '.$host);
            }
            foreach ($records as $r) {
                if (! empty($r['ip'])) {
                    $ips[] = $r['ip'];
                }
                if (! empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }

        if ($ips === []) {
            throw new \RuntimeException('Host tidak memiliki alamat IP.');
        }

        // Rentang dokumentasi (RFC 5737) tidak ditolak filter_var di
        // sebagian versi PHP — tolak eksplisit di sini.
        $blockedCidrs = [
            '192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24',
            '192.0.0.0/24', '2001:db8::/32',
        ];

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \RuntimeException('Alamat tujuan tidak diizinkan.');
            }
            // Sabuk pengaman eksplisit untuk loopback
            if (str_starts_with($ip, '127.') || $ip === '::1') {
                throw new \RuntimeException('Alamat tujuan tidak diizinkan.');
            }
            foreach ($blockedCidrs as $cidr) {
                if (self::ipInCidr($ip, $cidr)) {
                    throw new \RuntimeException('Alamat tujuan tidak diizinkan.');
                }
            }
        }
    }

    /**
     * Cek apakah IP berada dalam CIDR (IPv4/IPv6).
     */
    public static function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bytes = strlen($ipBin);
        $fullBytes = intdiv($bits, 8);
        $remaining = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }
        if ($remaining > 0) {
            $mask = 0xFF << (8 - $remaining) & 0xFF;
            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($subnetBin[$fullBytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * URL aman untuk log: tanpa query string (bisa mengandung secret
     * bila auth api_key_query) dan tanpa fragment.
     */
    public static function safeLogUrl(string $url): string
    {
        $p = parse_url($url);
        if ($p === false) {
            return '(url tidak valid)';
        }

        return ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '?').($p['path'] ?? '/');
    }
}
