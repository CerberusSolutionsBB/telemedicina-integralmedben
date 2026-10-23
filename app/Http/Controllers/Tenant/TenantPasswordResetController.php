<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * "Esqueci a senha" do painel do parceiro. O broker usa o banco do tenant
 * (password_reset_tokens e users), pois a tenancy já foi inicializada pelo domínio.
 */
class TenantPasswordResetController extends Controller
{
    private const ROTAS = [
        'email' => 'tenant.password.email',
        'store' => 'tenant.password.store',
        'login' => 'tenant.login',
    ];

    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'rotas' => self::ROTAS,
            'expiraMinutos' => config('auth.passwords.users.expire'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->bloquearParceiroInativo();

        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (TransportExceptionInterface $e) {
            // Falha do provedor de e-mail (Resend): registra o motivo e avisa, sem tela de erro 500.
            Log::warning('Falha ao enviar e-mail de redefinição de senha', ['erro' => $e->getMessage()]);

            // Sem e-mail entregue, o token não vale nada e travaria a nova tentativa ("aguarde").
            if ($user = Password::getUser($request->only('email'))) {
                Password::deleteToken($user);
            }

            throw ValidationException::withMessages([
                'email' => ['Não conseguimos enviar o e-mail agora. Tente novamente em alguns minutos.'],
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // Inclui e-mail não cadastrado neste parceiro ("passwords.user") e excesso de pedidos.
        throw ValidationException::withMessages(['email' => [__($status)]]);
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'rotas' => self::ROTAS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->bloquearParceiroInativo();

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('tenant.login')->with('status', __($status));
        }

        throw ValidationException::withMessages(['email' => [__($status)]]);
    }

    private function bloquearParceiroInativo(): void
    {
        if (tenant() && ! tenant()->status) {
            throw ValidationException::withMessages([
                'email' => 'Este parceiro está inativado. Acesso negado.',
            ]);
        }
    }
}
