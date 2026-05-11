<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantAuthController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Tenant/Login');
    }

    public function login(Request $request)
    {
        try {

            $credentials = $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required'],
            ]);

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
                Log::warning('Falha no login tenant', [
                    'email'     => $request->email,
                    'tenant_id' => tenant('id'),
                    'database'  => DB::connection()->getDatabaseName(),
                ]);

                return back()
                    ->withErrors([
                        'email' => 'Credenciais inválidas para este tenant ou usuário não encontrado.',
                    ])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('cpanel.patients.index'));

        } catch (\Throwable $e) {
            Log::error('Erro no login tenant', [
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'tenant_id' => tenant('id'),
                'email'     => $request->email,
                'database'  => DB::connection()->getDatabaseName(),
            ]);

            return back()
                ->withErrors([
                    'email' => 'Erro ao autenticar.',
                ])
                ->onlyInput('email');
        }
    }

    public function logout(Request $request)
    {

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
