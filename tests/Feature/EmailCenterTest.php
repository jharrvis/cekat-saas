<?php

namespace Tests\Feature;

use App\Livewire\Admin\EmailCampaignManager;
use App\Livewire\Admin\EmailLogManager;
use App\Livewire\Admin\EmailTemplateManager;
use App\Mail\CampaignEmail;
use App\Mail\EmailOtp;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Email\CampaignSender;
use App\Services\Email\EmailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Email Center: every outbound email lands in email_logs (sent or
 * failed), admin templates/campaigns are managed from the admin page,
 * and campaigns drain in cursor-driven chunks via campaigns:send.
 */
class EmailCenterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_email_center_page_requires_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('admin.email-center'))
            ->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('admin.email-center'))
            ->assertOk()
            ->assertSee('Pusat Email')
            ->assertSee('Log Email');
    }

    public function test_successful_send_writes_email_log(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->sendEmailVerificationNotification();

        Mail::assertSent(EmailOtp::class, 1);

        $log = EmailLog::where('category', 'otp')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame($user->email, $log->recipient);
        $this->assertNotNull($log->subject);
        $this->assertNotNull($log->body);
        $this->assertSame($user->id, $log->meta['user_id'] ?? null);
    }

    public function test_failed_send_is_logged_and_rethrown(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        try {
            EmailSender::send('target@test.id', new CampaignEmail('Subjek', '<p>Isi</p>', 'Uji', 'Uji'), 'test');
            $this->fail('EmailSender should rethrow transport failures.');
        } catch (\RuntimeException $e) {
            $this->assertSame('SMTP down', $e->getMessage());
        }

        $log = EmailLog::where('status', 'failed')->first();
        $this->assertNotNull($log);
        $this->assertSame('test', $log->category);
        $this->assertSame('target@test.id', $log->recipient);
        $this->assertStringContainsString('SMTP down', $log->error);
    }

    public function test_template_crud_works(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailTemplateManager::class)
            ->set('name', 'Newsletter Bulanan')
            ->set('subject', 'Update bulan ini')
            ->set('body', '<p>Halo {{name}}</p>')
            ->set('category', 'newsletter')
            ->call('save')
            ->assertHasNoErrors();

        $template = EmailTemplate::first();
        $this->assertNotNull($template);
        $this->assertSame('newsletter-bulanan', $template->slug);

        Livewire::actingAs($admin)
            ->test(EmailTemplateManager::class)
            ->call('openEdit', $template->id)
            ->set('subject', 'Update revisi')
            ->call('save');

        $this->assertSame('Update revisi', $template->fresh()->subject);

        Livewire::actingAs($admin)
            ->test(EmailTemplateManager::class)
            ->call('delete', $template->id);

        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
    }

    public function test_template_validation_requires_name_subject_body(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailTemplateManager::class)
            ->call('save')
            ->assertHasErrors(['name', 'subject', 'body']);
    }

    public function test_template_preview_renders_sample_tokens(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailTemplateManager::class)
            ->set('name', 'Preview')
            ->set('subject', 'Halo {{name}}')
            ->set('body', '<p>Nama: {{name}}, Email: {{email}}</p>')
            ->call('preview')
            ->assertSet('previewShow', true);

        // The same pipeline preview() uses: token replacement + fixed shell.
        [$subject, $body] = EmailTemplate::renderTokens('Halo {{name}}', '<p>Nama: {{name}}, Email: {{email}}</p>', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);
        $html = view('emails.campaign', [
            'body' => $body,
            'title' => $subject,
            'category' => 'Pratinjau Template',
        ])->render();

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('{{name}}', $html);
    }

    public function test_template_test_send_is_logged(): void
    {
        Mail::fake();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailTemplateManager::class)
            ->set('name', 'Uji')
            ->set('subject', 'Subjek uji')
            ->set('body', '<p>Isi uji {{name}}</p>')
            ->call('sendTest');

        Mail::assertSent(CampaignEmail::class, 1);

        $log = EmailLog::where('category', 'test')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame($admin->email, $log->recipient);
    }

    public function test_newsletter_segments_snapshot_and_chunked_send(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $verified = User::factory()->count(3)->create();
        User::factory()->unverified()->create();

        Livewire::actingAs($admin)
            ->test(EmailCampaignManager::class, ['type' => 'newsletter'])
            ->set('name', 'Update Oktober')
            ->set('subject', 'Kabar Oktober')
            ->set('body', '<p>Halo {{name}}</p>')
            ->call('save')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::first();
        $this->assertSame('newsletter', $campaign->type);

        $sender = app(CampaignSender::class);
        $this->assertTrue($sender->start($campaign));
        $this->assertSame('sending', $campaign->fresh()->status);
        $this->assertSame(3, $campaign->fresh()->total_recipients);
        $this->assertCount(3, $campaign->fresh()->recipients);

        $this->artisan('campaigns:send')->assertSuccessful();

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(3, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertNotNull($campaign->sent_at);

        Mail::assertSent(CampaignEmail::class, 3);
        $this->assertSame(3, EmailLog::where('campaign_id', $campaign->id)
            ->where('category', 'campaign-newsletter')
            ->where('status', 'sent')
            ->count());

        // Unverified user was excluded from the snapshot.
        foreach ($verified as $user) {
            $this->assertDatabaseHas('email_logs', ['recipient' => $user->email, 'campaign_id' => $campaign->id]);
        }
    }

    public function test_campaign_with_empty_segment_cannot_start(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create();

        Livewire::actingAs($admin)
            ->test(EmailCampaignManager::class, ['type' => 'announcement'])
            ->set('name', 'Pengumuman kosong')
            ->set('subject', 'Tanpa penerima')
            ->set('body', '<p>Halo</p>')
            ->set('segmentStatus', 'banned')
            ->call('save')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::first();
        $sender = app(CampaignSender::class);

        $this->assertFalse($sender->start($campaign));
        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_stopped_campaign_resumes_without_duplicates(): void
    {
        Mail::fake();
        $admin = $this->admin();
        User::factory()->count(4)->create();

        Livewire::actingAs($admin)
            ->test(EmailCampaignManager::class, ['type' => 'newsletter'])
            ->set('name', 'Lanjut nanti')
            ->set('subject', 'Subjek')
            ->set('body', '<p>Halo {{name}}</p>')
            ->call('save');

        $campaign = EmailCampaign::first();
        $sender = app(CampaignSender::class);

        $this->assertTrue($sender->start($campaign));
        $sender->sendChunk($campaign->fresh(), 2);
        $this->assertSame(2, $campaign->fresh()->cursor);

        $campaign->fresh()->update(['status' => 'stopped']);
        $sender->sendChunk($campaign->fresh(), 2); // stopped: no-op
        $this->assertSame(2, $campaign->fresh()->cursor);

        // Resume keeps the snapshot and cursor - no duplicate sends.
        $this->assertTrue($sender->start($campaign->fresh()));
        $campaign->refresh();
        $this->assertSame('sending', $campaign->status);
        $this->assertSame(2, $campaign->cursor);

        $this->artisan('campaigns:send')->assertSuccessful();

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(4, $campaign->sent_count);
        Mail::assertSent(CampaignEmail::class, 4);
    }

    public function test_campaign_types_are_listed_separately(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailCampaignManager::class, ['type' => 'newsletter'])
            ->set('name', 'N1')->set('subject', 'S')->set('body', '<p>x</p>')
            ->call('save');

        Livewire::actingAs($admin)
            ->test(EmailCampaignManager::class, ['type' => 'announcement'])
            ->set('name', 'A1')->set('subject', 'S')->set('body', '<p>x</p>')
            ->call('save');

        $this->assertSame(1, EmailCampaign::where('type', 'newsletter')->count());
        $this->assertSame(1, EmailCampaign::where('type', 'announcement')->count());

        Livewire::actingAs($admin)
            ->test(EmailCampaignManager::class, ['type' => 'newsletter'])
            ->assertSee('N1')
            ->assertDontSee('A1');
    }

    public function test_log_manager_filters_and_shows_detail(): void
    {
        Mail::fake();
        $admin = $this->admin();

        EmailLog::create([
            'category' => 'welcome',
            'mailable' => 'App\Mail\WelcomeUser',
            'recipient' => 'satu@test.id',
            'subject' => 'Selamat datang',
            'status' => 'sent',
            'body' => '<p>Isi email satu</p>',
        ]);
        EmailLog::create([
            'category' => 'payment',
            'recipient' => 'dua@test.id',
            'subject' => 'Pembayaran berhasil',
            'status' => 'failed',
            'error' => 'SMTP down',
        ]);

        Livewire::actingAs($admin)
            ->test(EmailLogManager::class)
            ->assertSee('satu@test.id')
            ->assertSee('dua@test.id')
            ->set('status', 'failed')
            ->assertDontSee('satu@test.id')
            ->assertSee('dua@test.id')
            ->set('category', 'payment')
            ->call('openDetail', EmailLog::where('recipient', 'dua@test.id')->first()->id)
            ->assertSee('SMTP down');
    }

    public function test_prune_removes_old_logs_and_keeps_recent(): void
    {
        $old = EmailLog::create([
            'category' => 'otp',
            'recipient' => 'lama@test.id',
            'subject' => 'Lama',
            'status' => 'sent',
        ]);
        $old->created_at = now()->subDays(100);
        $old->save();

        $recent = EmailLog::create([
            'category' => 'otp',
            'recipient' => 'baru@test.id',
            'subject' => 'Baru',
            'status' => 'sent',
        ]);

        $this->artisan('email:prune')->assertSuccessful();

        $this->assertDatabaseMissing('email_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('email_logs', ['id' => $recent->id]);
    }
}
