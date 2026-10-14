<?php

namespace Tests\Unit;

use App\Services\Chat\ApiLookupService;
use PHPUnit\Framework\TestCase;

/**
 * Unit test untuk logika murni ApiLookupService (metode statis,
 * tanpa facade Laravel) — dapat dijalankan standalone.
 */
class ApiLookupServiceTest extends TestCase
{
    public function test_substitute_url_template_encodes_params(): void
    {
        $url = ApiLookupService::substituteUrlTemplate(
            'https://api.pa-salatiga.go.id/list_perkara.php?search={nomor_perkara}&page={page}',
            ['nomor_perkara' => '49/Pdt.P/2026/PA.Sal', 'page' => '1'],
            [['name' => 'nomor_perkara'], ['name' => 'page']]
        );

        $this->assertSame(
            'https://api.pa-salatiga.go.id/list_perkara.php?search=49%2FPdt.P%2F2026%2FPA.Sal&page=1',
            $url
        );
    }

    public function test_substitute_url_template_rejects_undefined_placeholder(): void
    {
        $this->expectException(\RuntimeException::class);
        ApiLookupService::substituteUrlTemplate(
            'https://x.test/?a={tidak_terdaftar}',
            [],
            [['name' => 'nomor_perkara']]
        );
    }

    public function test_substitute_url_template_missing_param_becomes_empty(): void
    {
        $url = ApiLookupService::substituteUrlTemplate(
            'https://x.test/?a={p}',
            [],
            [['name' => 'p']]
        );
        $this->assertSame('https://x.test/?a=', $url);
    }

    public function test_array_get_dot_and_bracket_paths(): void
    {
        $data = ['data' => [['nomor_perkara' => '49/Pdt.P/2026/PA.Sal', 'status' => 'Sidang']]];

        $this->assertSame('49/Pdt.P/2026/PA.Sal', ApiLookupService::arrayGet($data, 'data.0.nomor_perkara'));
        $this->assertSame('49/Pdt.P/2026/PA.Sal', ApiLookupService::arrayGet($data, 'data[0].nomor_perkara'));
        $this->assertSame('Sidang', ApiLookupService::arrayGet($data, 'data[0].status'));
        $this->assertNull(ApiLookupService::arrayGet($data, 'data.5.nomor_perkara'));
        $this->assertNull(ApiLookupService::arrayGet($data, 'tidak.ada'));
    }

    public function test_map_response_converts_types(): void
    {
        $json = [
            'data' => [['nomor' => '1/Pdt.G/2026/PA.Sal', 'aktif' => true, 'tag' => ['a', 'b']]],
            'total' => 5,
        ];
        $mapped = ApiLookupService::mapResponse($json, [
            'nomor_perkara' => 'data.0.nomor',
            'status_aktif' => 'data.0.aktif',
            'tag' => 'data.0.tag',
            'total' => 'total',
            'hilang' => 'data.0.tidak_ada',
        ]);

        $this->assertSame('1/Pdt.G/2026/PA.Sal', $mapped['nomor_perkara']);
        $this->assertSame('ya', $mapped['status_aktif']);
        $this->assertSame('a, b', $mapped['tag']);
        $this->assertSame('5', $mapped['total']);
        $this->assertSame('', $mapped['hilang']);
    }

    public function test_map_response_truncates_long_values(): void
    {
        $mapped = ApiLookupService::mapResponse(
            ['x' => str_repeat('a', 600)],
            ['x' => 'x']
        );
        $this->assertSame(500, mb_strlen($mapped['x']));
    }

    public function test_render_template_substitutes_fields(): void
    {
        $out = ApiLookupService::renderTemplate(
            'Perkara {nomor_perkara} berstatus {status}. {tak_ada}',
            ['nomor_perkara' => '1/Pdt.G/2026/PA.Sal', 'status' => 'Sidang']
        );
        $this->assertSame('Perkara 1/Pdt.G/2026/PA.Sal berstatus Sidang. ', $out);
        $this->assertSame('', ApiLookupService::renderTemplate(null, []));
    }

    public function test_resolve_url(): void
    {
        $this->assertSame(
            'https://a.test/x',
            ApiLookupService::resolveUrl('https://a.test/y', 'https://a.test/x')
        );
        $this->assertSame(
            'https://a.test/abs',
            ApiLookupService::resolveUrl('https://a.test/dir/page', '/abs')
        );
        $this->assertSame(
            'https://a.test/dir/rel',
            ApiLookupService::resolveUrl('https://a.test/dir/page', 'rel')
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('blockedUrls')]
    public function test_assert_url_safe_blocks_dangerous_urls(string $url): void
    {
        $this->expectException(\RuntimeException::class);
        ApiLookupService::assertUrlSafe($url);
    }

    public static function blockedUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/x'],
            'loopback https' => ['https://127.0.0.1/'],
            'private 10/8' => ['http://10.0.0.5/'],
            'private 192.168/16' => ['http://192.168.1.1/'],
            'private 172.16/12' => ['http://172.20.0.1/'],
            'metadata' => ['http://169.254.169.254/latest/'],
            'test-net reserved' => ['http://192.0.2.1/'],
            'ftp scheme' => ['ftp://8.8.8.8/'],
            'file scheme' => ['file:///etc/passwd'],
            'userinfo' => ['http://user:pass@8.8.8.8/'],
            'bukan url' => ['not a url'],
        ];
    }

    public function test_assert_url_safe_allows_public_ip(): void
    {
        // IP literal publik tidak butuh DNS — aman diuji di mana saja.
        ApiLookupService::assertUrlSafe('https://8.8.8.8/');
        ApiLookupService::assertUrlSafe('http://1.1.1.1/dns-query');
        $this->assertTrue(true);
    }

    public function test_safe_log_url_strips_query(): void
    {
        $this->assertSame(
            'https://api.x.test/list',
            ApiLookupService::safeLogUrl('https://api.x.test/list?api_key=RAHASIA&search=a')
        );
    }
}
