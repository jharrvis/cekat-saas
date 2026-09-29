<?php

namespace Tests\Feature;

use App\Livewire\Admin\PlanManager;
use App\Livewire\KnowledgeBaseEditor;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppDevice;
use App\Models\Widget;
use App\Services\Billing\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PlanLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function makeUserWith(array $planAttrs, string $role = 'user', array $userAttrs = []): User
    {
        $this->seq++;

        $plan = Plan::create(array_merge([
            'name' => 'Plan ' . $this->seq,
            'slug' => 'plan-' . $this->seq . '-' . uniqid(),
        ], $planAttrs));

        return User::create(array_merge([
            'name' => 'User ' . $this->seq,
            'email' => 'user-' . $this->seq . '-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => $role,
            'plan_id' => $plan->id,
        ], $userAttrs));
    }

    private function makeWidget(User $user, string $status): Widget
    {
        return Widget::create([
            'user_id' => $user->id,
            'name' => 'w-' . uniqid(),
            'slug' => 'w-' . uniqid(),
            'status' => $status,
            'is_active' => $status === 'active',
        ]);
    }

    public function test_user_cannot_create_channel_beyond_plan_limit(): void
    {
        $user = $this->makeUserWith(['max_widgets' => 1]);
        $this->makeWidget($user, 'active');

        $response = $this->actingAs($user)
            ->get(route('channels.create'))
            ->assertRedirect(route('channels.index'));

        $response->assertSessionHas('error');
    }

    public function test_store_channel_beyond_plan_limit_is_rejected(): void
    {
        $user = $this->makeUserWith(['max_widgets' => 1]);
        $this->makeWidget($user, 'active');

        $response = $this->actingAs($user)
            ->post(route('channels.store'), ['display_name' => 'Baru'])
            ->assertRedirect(route('channels.index'));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('widgets', 1);
    }

    public function test_user_cannot_activate_channel_beyond_active_limit(): void
    {
        $user = $this->makeUserWith(['max_widgets' => 1]);
        $this->makeWidget($user, 'active');
        $draft = $this->makeWidget($user, 'draft');

        $response = $this->actingAs($user)
            ->post(route('channels.activate', $draft->id))
            ->assertRedirect(route('channels.index'));

        $response->assertSessionHas('error');
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_user_with_only_draft_widget_still_blocked_from_creating_more(): void
    {
        $user = $this->makeUserWith(['max_widgets' => 1]);
        $draft = $this->makeWidget($user, 'draft');

        $response = $this->actingAs($user)
            ->get(route('channels.create'))
            ->assertRedirect(route('channels.index'));

        $response->assertSessionHas('error');
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertDatabaseCount('widgets', 1);
    }

    public function test_whatsapp_device_limit_is_enforced_from_plan(): void
    {
        Setting::set('whatsapp_module_enabled', true, 'boolean');
        Setting::set('fonnte_account_token', 'test-token', 'string');

        $user = $this->makeUserWith([
            'can_use_whatsapp' => true,
            'max_whatsapp_devices' => 1,
        ]);

        WhatsAppDevice::create([
            'user_id' => $user->id,
            'device_name' => 'D1',
            'phone_number' => '628111111111',
            'status' => 'connected',
        ]);

        $response = $this->actingAs($user)->post(route('whatsapp.create'), [
            'device_name' => 'D2',
            'phone_number' => '08123456789',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, WhatsAppDevice::where('user_id', $user->id)->count());
    }

    public function test_registration_binds_default_free_plan(): void
    {
        $free = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Plan::create([
            'name' => 'Pro',
            'slug' => 'pro-' . uniqid(),
            'price' => 99000,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $email = 'register-' . uniqid() . '@test.id';
        $response = $this->post('/register', [
            'name' => 'Registered User',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::where('email', $email)->firstOrFail();

        $this->assertSame($free->id, $user->plan_id);
        $this->assertArrayNotHasKey('plan_tier', $user->getAttributes());
    }

    public function test_registration_without_any_free_plan_leaves_plan_id_null(): void
    {
        $response = $this->post('/register', [
            'name' => 'No Plan User',
            'email' => 'noplan-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::orderBy('id', 'desc')->first();
        $this->assertNull($user->plan_id);
        $this->assertSame(100, app(PlanLimitService::class)->limit($user, 'monthly_messages'));
    }

    public function test_faq_within_plan_limit_is_allowed(): void
    {
        $user = $this->makeUserWith(['max_faqs' => 1]);
        $this->actingAs($user);

        Livewire::test(KnowledgeBaseEditor::class)
            ->set('newFaqQuestion', 'Q1')
            ->set('newFaqAnswer', 'A1')
            ->call('addFaq');

        $this->assertDatabaseCount('knowledge_faqs', 1);
    }

    public function test_faq_add_is_rejected_beyond_plan_limit(): void
    {
        $user = $this->makeUserWith(['max_faqs' => 1]);
        $this->actingAs($user);

        Livewire::test(KnowledgeBaseEditor::class);

        $kb = KnowledgeBase::firstOrFail();
        $kb->faqs()->create(['question' => 'Q1', 'answer' => 'A1', 'sort_order' => 1]);

        $component = Livewire::test(KnowledgeBaseEditor::class)
            ->set('newFaqQuestion', 'Q2')
            ->set('newFaqAnswer', 'A2')
            ->call('addFaq');

        $this->assertSame(1, $kb->faqs()->count());
        $component->assertSee('FAQ limit of your plan');
    }

    public function test_document_upload_is_rejected_beyond_plan_count(): void
    {
        $user = $this->makeUserWith([
            'max_documents' => 1,
            'max_file_size_mb' => 5,
        ]);
        $this->actingAs($user);

        Livewire::test(KnowledgeBaseEditor::class);

        $kb = KnowledgeBase::firstOrFail();
        $kb->documents()->create([
            'name' => 'old.txt',
            'type' => 'txt',
            'file_path' => 'documents/old.txt',
            'status' => 'completed',
        ]);

        $component = Livewire::test(KnowledgeBaseEditor::class)
            ->set('uploadedFile', UploadedFile::fake()->createWithContent('new.txt', 'hello'))
            ->call('uploadFile');

        $this->assertSame(1, KnowledgeDocument::count());
        $component->assertSee('Document limit of your plan');
    }

    public function test_document_upload_is_rejected_beyond_plan_file_size(): void
    {
        $user = $this->makeUserWith([
            'max_documents' => 10,
            'max_file_size_mb' => 1,
        ]);
        $this->actingAs($user);

        Livewire::test(KnowledgeBaseEditor::class)
            ->set('uploadedFile', UploadedFile::fake()->createWithContent(
                'big.txt',
                str_repeat('A', 1024 * 1024 + 64)
            ))
            ->call('uploadFile')
            ->assertHasErrors('uploadedFile');

        $this->assertSame(0, KnowledgeDocument::count());
    }

    public function test_ui_pages_display_plan_limits_from_service(): void
    {
        $user = $this->makeUserWith([
            'max_widgets' => 3,
            'max_messages_per_month' => 777,
        ]);
        $user->monthly_message_used = 12;
        $user->save();
        $this->makeWidget($user, 'active');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('777');

        $this->actingAs($user)->get(route('channels.index'))
            ->assertOk()
            ->assertSee('of 3 allowed')
            ->assertSee('of 777');

        $this->actingAs($user)->get(route('billing'))
            ->assertOk()
            ->assertSee('777');
    }

    public function test_plan_manager_changes_are_used_by_service_immediately(): void
    {
        $user = $this->makeUserWith([
            'max_widgets' => 1,
            'max_messages_per_month' => 100,
            'chat_history_days' => 7,
        ]);
        $plan = $user->plan;
        $limits = app(PlanLimitService::class);

        $this->assertFalse($limits->check($user, 'total_channels', ['used' => 1])['allowed']);

        Livewire::test(PlanManager::class)
            ->call('editPlan', $plan->id)
            ->set('max_widgets', 3)
            ->set('max_messages_per_month', 555)
            ->set('chat_history_days', 14)
            ->set('max_whatsapp_devices', 5)
            ->set('features', ['leads' => true, 'whatsapp' => false, 'analytics' => true])
            ->call('savePlan');

        $fresh = $plan->fresh();
        $this->assertSame(3, (int) $fresh->max_widgets);
        $this->assertSame(555, (int) $fresh->max_messages_per_month);
        $this->assertSame(14, (int) $fresh->chat_history_days);
        $this->assertSame(5, (int) $fresh->max_whatsapp_devices);
        $this->assertTrue((bool) $fresh->can_export_leads);
        $this->assertFalse((bool) $fresh->can_use_whatsapp);
        $this->assertArrayNotHasKey('leads', $fresh->features ?? []);

        $user = $user->fresh();
        $this->assertTrue($limits->check($user, 'total_channels', ['used' => 2])['allowed']);
        $this->assertSame(555, $limits->limit($user, 'monthly_messages'));
        $this->assertSame(5, $limits->limit($user, 'whatsapp_devices'));
        $this->assertTrue($limits->feature($user, 'leads'));

        $this->actingAs($user)->get(route('billing'))
            ->assertOk()
            ->assertSee('555');
    }
}

