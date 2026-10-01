<?php

namespace Tests\Unit;

use App\Support\VisitorGeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Visitor geo/device helper: coarse device class + short browser label
 * for chat history, best-effort IP lookup that never breaks persistence
 * and never runs for private addresses (tests/local dev).
 */
class VisitorGeoTest extends TestCase
{
    public function test_device_type_classification(): void
    {
        $this->assertSame('desktop', VisitorGeo::deviceType(null));
        $this->assertSame('desktop', VisitorGeo::deviceType(''));
        $this->assertSame(
            'desktop',
            VisitorGeo::deviceType('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/153.0.0.0 Safari/537.36')
        );
        $this->assertSame(
            'mobile',
            VisitorGeo::deviceType('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1')
        );
        $this->assertSame(
            'mobile',
            VisitorGeo::deviceType('Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0 Mobile Safari/537.36')
        );
        $this->assertSame(
            'tablet',
            VisitorGeo::deviceType('Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1')
        );
    }

    public function test_describe_agent_gives_short_human_label(): void
    {
        $this->assertNull(VisitorGeo::describeAgent(null));
        $this->assertNull(VisitorGeo::describeAgent(''));

        $this->assertSame(
            'Chrome 153 · Windows',
            VisitorGeo::describeAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/153.0.0.0 Safari/537.36')
        );

        // Edge/Opera user agents also contain "Chrome/" - the first match wins.
        $this->assertSame(
            'Edge 129 · Windows',
            VisitorGeo::describeAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0')
        );

        $this->assertSame(
            'Firefox 128 · Linux',
            VisitorGeo::describeAgent('Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0')
        );

        $this->assertSame(
            'Safari · macOS',
            VisitorGeo::describeAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/17.0 Safari/605.1.15')
        );

        $this->assertSame(
            'Chrome 120 · Android',
            VisitorGeo::describeAgent('Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36')
        );
    }

    public function test_is_public_ip_rejects_loopback_private_and_invalid(): void
    {
        $this->assertTrue(VisitorGeo::isPublicIp('8.8.8.8'));
        $this->assertTrue(VisitorGeo::isPublicIp('2606:4700:4700::1111'));

        $this->assertFalse(VisitorGeo::isPublicIp(null));
        $this->assertFalse(VisitorGeo::isPublicIp(''));
        $this->assertFalse(VisitorGeo::isPublicIp('not-an-ip'));
        $this->assertFalse(VisitorGeo::isPublicIp('127.0.0.1'));
        $this->assertFalse(VisitorGeo::isPublicIp('10.11.12.13'));
        $this->assertFalse(VisitorGeo::isPublicIp('192.168.1.1'));
        $this->assertFalse(VisitorGeo::isPublicIp('172.16.0.1'));
        $this->assertFalse(VisitorGeo::isPublicIp('::1'));
    }

    public function test_resolve_public_ip_calls_provider_and_maps_fields(): void
    {
        Http::fake([
            'ipwho.is/*' => Http::response([
                'success' => true,
                'country' => 'Indonesia',
                'country_code' => 'ID',
                'region' => 'Jakarta',
                'city' => 'Jakarta',
                'connection' => ['isp' => 'Example ISP'],
            ]),
        ]);

        $geo = VisitorGeo::resolve('8.8.8.8');

        $this->assertNotNull($geo);
        $this->assertSame('ID', $geo['country_code']);
        $this->assertSame('Indonesia', $geo['country']);
        $this->assertSame('Jakarta', $geo['city']);
        $this->assertSame('Example ISP', $geo['isp']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'ipwho.is/8.8.8.8'));
    }

    public function test_resolve_private_ip_never_calls_provider(): void
    {
        Http::fake();

        $this->assertNull(VisitorGeo::resolve('127.0.0.1'));
        $this->assertNull(VisitorGeo::resolve('10.0.0.5'));

        Http::assertNothingSent();
    }

    public function test_resolve_keeps_cloudflare_country_when_lookup_skipped(): void
    {
        Http::fake();

        $this->app->instance('request', Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_IPCOUNTRY' => 'SG',
        ]));

        $geo = VisitorGeo::resolve('127.0.0.1');

        $this->assertSame('SG', $geo['country_code']);
        Http::assertNothingSent();
    }
}
