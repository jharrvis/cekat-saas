<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetApiLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * API Lookup: CRUD per channel (otorisasi pemilik), endpoint "Uji",
 * dan eksekusi service (mapping, hasil kosong, blokir SSRF).
 */
class ApiLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Widget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Lookup Owner',
            'email' => 'owner-lookup@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $this->widget = Widget::create([
            'user_id' => $this->owner->id,
            'name' => 'Widget Lookup',
            'slug' => 'w-lookup-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'key' => 'cari_perkara',
            'name' => 'Cari Perkara',
            'description' => 'Cari perkara berdasarkan nomor.',
            'method' => 'GET',
            'url_template' => 'https://api.x.test/list?search={nomor}',
            'params' => [
                ['name' => 'nomor', 'label' => 'Nomor', 'required' => '1', 'description' => '', 'example' => '1/Pdt.G/2026/PA.Sal'],
            ],
            'auth_type' => 'none',
            'timeout_seconds' => 8,
            'mapping_rows' => [
                ['field' => 'nomor_perkara', 'path' => 'data.0.nomor'],
                ['field' => 'status', 'path' => 'data.0.status'],
            ],
            'empty_path' => 'data',
            'reply_template' => 'Perkara {nomor_perkara} berstatus {status}.',
            'empty_message' => 'Tidak ditemukan.',
            'error_message' => 'API error.',
            'cache_ttl_seconds' => 0,
            'is_active' => '1',
        ], $over);
    }

    private function makeLookup(array $over = []): WidgetApiLookup
    {
        return $this->widget->apiLookups()->create([
            'key' => 'cari_perkara',
            'name' => 'Cari Perkara',
            'method' => 'GET',
            'url_template' => 'https://api.x.test/list?search={nomor}',
            'params' => [['name' => 'nomor', 'label' => 'Nomor', 'required' => true]],
            'auth_type' => 'none',
            'timeout_seconds' => 8,
            'response_mapping' => ['nomor_perkara' => 'data.0.nomor', 'status' => 'data.0.status'],
            'empty_path' => 'data',
            'reply_template' => 'Perkara {nomor_perkara} berstatus {status}.',
            'empty_message' => 'Tidak ditemukan.',
            'error_message' => 'API error.',
            'cache_ttl_seconds' => 0,
            'is_active' => true,
            ...$over,
        ]);
    }

    public function test_store_creates_lookup_and_encrypts_secret(): void
    {
        $res = $this->actingAs($this->owner)->post(
            route('channels.api-lookups.store', $this->widget->id),
            $this->payload([
                'auth_type' => 'bearer',
                'auth_secret' => 'token-rahasia-123',
            ])
        );

        $res->assertRedirect();
        $row = WidgetApiLookup::where('widget_id', $this->widget->id)->where('key', 'cari_perkara')->first();
        $this->assertNotNull($row);
        $this->assertSame('token-rahasia-123', $row->auth_secret); // terdekripsi otomatis
        $raw = DB::table('widget_api_lookups')->where('id', $row->id)->value('auth_secret');
        $this->assertNotSame('token-rahasia-123', $raw); // tersimpan terenkripsi
        $this->assertSame('nomor', $row->params[0]['name']);
        $this->assertSame('data.0.nomor', $row->response_mapping['nomor_perkara']);
    }

    public function test_store_rejects_duplicate_key_per_widget(): void
    {
        $this->makeLookup();
        $res = $this->actingAs($this->owner)->post(
            route('channels.api-lookups.store', $this->widget->id),
            $this->payload()
        );
        $res->assertSessionHasErrors('key');
    }

    public function test_store_rejects_undefined_placeholder(): void
    {
        $res = $this->actingAs($this->owner)->post(
            route('channels.api-lookups.store', $this->widget->id),
            $this->payload(['url_template' => 'https://api.x.test/list?search={tak_ada}'])
        );
        $res->assertSessionHasErrors('url_template');
    }

    public function test_cannot_manage_other_users_channel(): void
    {
        $other = User::create([
            'name' => 'Other', 'email' => 'other@test.id',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $res = $this->actingAs($other)->post(
            route('channels.api-lookups.store', $this->widget->id),
            $this->payload()
        );
        $res->assertNotFound();
    }

    public function test_update_keeps_secret_when_left_blank(): void
    {
        $row = $this->makeLookup(['auth_type' => 'bearer', 'auth_secret' => 'lama-123']);
        $this->actingAs($this->owner)->put(
            route('channels.api-lookups.update', [$this->widget->id, $row->id]),
            $this->payload(['name' => 'Nama Baru', 'auth_type' => 'bearer', 'auth_secret' => ''])
        );
        $this->assertSame('lama-123', $row->fresh()->auth_secret);
        $this->assertSame('Nama Baru', $row->fresh()->name);
    }

    public function test_destroy_deletes_lookup(): void
    {
        $row = $this->makeLookup();
        $this->actingAs($this->owner)->delete(
            route('channels.api-lookups.destroy', [$this->widget->id, $row->id])
        )->assertRedirect();
        $this->assertNull(WidgetApiLookup::find($row->id));
    }

    public function test_endpoint_returns_mapped_reply(): void
    {
        $row = $this->makeLookup();
        Http::fake(['*' => Http::response([
            'data' => [['nomor' => '1/Pdt.G/2026/PA.Sal', 'status' => 'Sidang']],
        ], 200)]);

        $res = $this->actingAs($this->owner)->postJson(
            route('channels.api-lookups.test', [$this->widget->id, $row->id]),
            ['test_params' => ['nomor' => '1/Pdt.G/2026/PA.Sal']]
        );

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('http_status', 200)
            ->assertJsonPath('mapped.nomor_perkara', '1/Pdt.G/2026/PA.Sal')
            ->assertJsonPath('reply', 'Perkara 1/Pdt.G/2026/PA.Sal berstatus Sidang.');
    }

    public function test_endpoint_uses_empty_message_when_no_result(): void
    {
        $row = $this->makeLookup();
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $res = $this->actingAs($this->owner)->postJson(
            route('channels.api-lookups.test', [$this->widget->id, $row->id]),
            ['test_params' => ['nomor' => '999/XXX/2026']]
        );

        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reply', 'Tidak ditemukan.');
    }

    public function test_endpoint_blocks_ssrf_target(): void
    {
        $row = $this->makeLookup(['url_template' => 'http://127.0.0.1:9999/?q={nomor}']);

        $res = $this->actingAs($this->owner)->postJson(
            route('channels.api-lookups.test', [$this->widget->id, $row->id]),
            ['test_params' => ['nomor' => 'x']]
        );

        $res->assertOk()->assertJsonPath('ok', false);
        $this->assertStringContainsString('tidak diizinkan', (string) $res->json('error'));
    }

    public function test_dispatch_returns_error_message_on_api_failure(): void
    {
        $row = $this->makeLookup();
        Http::fake(['*' => Http::response('boom', 500)]);

        $service = app(\App\Services\Chat\ApiLookupService::class);
        $reply = $service->dispatch($this->widget, [
            'action' => 'api_lookup',
            'lookup' => 'cari_perkara',
            'params' => ['nomor' => '1/Pdt.G/2026/PA.Sal'],
        ]);

        $this->assertSame('API error.', $reply);
    }

    public function test_dispatch_unknown_lookup_returns_unavailable(): void
    {
        $service = app(\App\Services\Chat\ApiLookupService::class);
        $reply = $service->dispatch($this->widget, [
            'action' => 'api_lookup',
            'lookup' => 'tak_ada',
            'params' => [],
        ]);

        $this->assertStringContainsString('tidak tersedia', $reply);
    }
}
