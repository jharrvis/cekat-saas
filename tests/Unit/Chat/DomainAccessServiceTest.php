<?php

namespace Tests\Unit\Chat;

use App\Services\Chat\DomainAccessService;
use Tests\TestCase;

class DomainAccessServiceTest extends TestCase
{
    private DomainAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DomainAccessService;
    }

    public function test_empty_allowlist_permits_any_origin(): void
    {
        $this->assertTrue($this->service->isAllowed(null, 'https://evil.test'));
        $this->assertTrue($this->service->isAllowed('', 'https://evil.test'));
        $this->assertTrue($this->service->isAllowed('   ', null));
    }

    public function test_missing_origin_header_is_allowed(): void
    {
        // Preserves legacy behavior: enforced only when Origin/Referer present.
        $this->assertTrue($this->service->isAllowed('example.com', null));
    }

    public function test_listed_domain_is_allowed(): void
    {
        $this->assertTrue($this->service->isAllowed('example.com, toko.id', 'https://toko.id/page'));
    }

    public function test_unlisted_domain_is_blocked(): void
    {
        $this->assertFalse($this->service->isAllowed('example.com', 'https://evil.test/chat'));
    }

    public function test_localhost_bypass_preserved(): void
    {
        $this->assertTrue($this->service->isAllowed('example.com', 'http://localhost:8000'));
        $this->assertTrue($this->service->isAllowed('example.com', 'http://127.0.0.1:8000'));
    }

    public function test_app_own_host_is_allowed_even_when_not_listed(): void
    {
        config(['app.url' => 'https://cekat-saas.test']);

        $this->assertTrue($this->service->isAllowed('example.com', 'https://cekat-saas.test/dashboard'));
        $this->assertFalse($this->service->isAllowed('example.com', 'https://evil.test/x'));
    }
}
