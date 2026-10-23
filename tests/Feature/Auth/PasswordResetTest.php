<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_email_nao_cadastrado_e_recusado_sem_enviar(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'ninguem@exemplo.com'])
            ->assertSessionHasErrors(['email' => __('passwords.user')])
            ->assertSessionMissing('status');

        Notification::assertNothingSent();
    }

    public function test_email_de_redefinicao_em_portugues_com_link_central(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            $this->assertStringStartsWith('Redefinição de senha', $mail->subject);
            $url = route('password.reset', ['token' => $notification->token, 'email' => $user->email]);
            $this->assertSame(['emails.redefinir-senha', 'emails.redefinir-senha-texto'], $mail->view);
            $this->assertSame($url, $mail->viewData['url']);
            $this->assertSame(public_path('images/logo-med-ben-email.jpg'), $mail->viewData['logo']);

            $html = (string) $mail->render();
            $this->assertStringContainsString('Redefinir senha', $html);
            $this->assertStringContainsString(e($url), $html);
            $this->assertStringContainsString('Olá, '.e($user->name).'!', $html);

            return true;
        });
    }

    public function test_link_de_redefinicao_aponta_para_o_parceiro_quando_ha_tenancy(): void
    {
        $user = User::factory()->make(['email' => 'parceiro@exemplo.com']);

        tenancy()->initialized = true;

        try {
            $url = (ResetPassword::$createUrlCallback)($user, 'abc');
        } finally {
            tenancy()->initialized = false;
        }

        $this->assertSame(route('tenant.password.reset', ['token' => 'abc', 'email' => 'parceiro@exemplo.com']), $url);
        $this->assertStringContainsString('/admin/reset-password/abc', $url);
    }

    public function test_falha_no_envio_mostra_aviso_e_permite_tentar_de_novo(): void
    {
        $user = User::factory()->create();

        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new TransportException('Resend recusou'));

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }
}
