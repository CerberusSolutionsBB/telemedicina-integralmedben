<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'rotas' => ['email' => 'password.email', 'store' => 'password.store', 'login' => 'login'],
            'expiraMinutos' => config('auth.passwords.users.expire'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
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

        // Inclui e-mail não cadastrado ("passwords.user") e excesso de pedidos.
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
