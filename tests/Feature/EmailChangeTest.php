<?php

namespace Tests\Feature;

use App\Mail\EmailChangeConfirm;
use App\Mail\EmailChangeDone;
use App\Mail\EmailChangeRequestAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Change-of-email flow: confirmation link to the NEW address, alert to the
 * OLD one, activation only via the signed link, done-notice on completion.
 */
class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => 'Change Tester',
            'email' => $email,
            'password' => 'secret123',
            'email_verified_at' => now(),
        ], $extra));
    }

    public function test_request_stores_pending_and_sends_both_emails(): void
    {
        Mail::fake();

        $user = $this->makeUser('old@test.id');

        $response = $this->actingAs($user)->put('/settings/email', [
            'email' => 'new@test.id',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertSame('new@test.id', $fresh->pending_email);
        $this->assertSame('old@test.id', $fresh->email);

        Mail::assertSent(EmailChangeConfirm::class, fn ($m) => $m->hasTo('new@test.id'));
        Mail::assertSent(EmailChangeRequestAlert::class, fn ($m) => $m->hasTo('old@test.id'));
    }

    public function test_signed_confirm_activates_email_and_notifies_old_address(): void
    {
        Mail::fake();

        $user = $this->makeUser('old@test.id', ['pending_email' => 'new@test.id']);

        $url = URL::temporarySignedRoute('settings.email.confirm', now()->addDay(), [
            'id' => $user->id,
            'hash' => sha1('new@test.id'),
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertRedirect(route('settings'));

        $fresh = $user->fresh();
        $this->assertSame('new@test.id', $fresh->email);
        $this->assertNull($fresh->pending_email);
        $this->assertNotNull($fresh->email_verified_at);

        Mail::assertSent(EmailChangeDone::class, fn ($m) => $m->hasTo('old@test.id'));
    }

    public function test_wrong_hash_does_not_change_email(): void
    {
        Mail::fake();

        $user = $this->makeUser('old@test.id', ['pending_email' => 'new@test.id']);

        $url = URL::temporarySignedRoute('settings.email.confirm', now()->addDay(), [
            'id' => $user->id,
            'hash' => sha1('other@test.id'),
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertSessionHasErrors('email');

        $fresh = $user->fresh();
        $this->assertSame('old@test.id', $fresh->email);
        $this->assertSame('new@test.id', $fresh->pending_email);
        Mail::assertNotSent(EmailChangeDone::class);
    }

    public function test_email_taken_by_another_user_is_rejected(): void
    {
        Mail::fake();

        $this->makeUser('taken@test.id');
        $user = $this->makeUser('old@test.id');

        $this->actingAs($user)
            ->put('/settings/email', ['email' => 'taken@test.id'])
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->pending_email);
        Mail::assertNotSent(EmailChangeConfirm::class);
    }

    public function test_same_as_current_email_is_rejected(): void
    {
        Mail::fake();

        $user = $this->makeUser('old@test.id');

        $this->actingAs($user)
            ->put('/settings/email', ['email' => 'old@test.id'])
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->pending_email);
    }
}
