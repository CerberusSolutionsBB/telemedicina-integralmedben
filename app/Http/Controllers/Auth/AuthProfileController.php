<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthProfileController extends Controller
{
    /**
     * Mostrar página de perfil
     * Não precisa passar user - vem do auth global
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/Profile', [
            'status' => session('status'),
        ]);
    }

    /**
     * Atualizar informações do perfil
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$request->user()->id,
        ]);

        $request->user()->update($validated);

        return redirect()->route('profile.edit')
            ->with('status', 'Perfil atualizado com sucesso!')
            ->with('type', 'success');
    }

    /**
     * Atualizar senha
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string|current_password',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $request->user()->update([
            'password' => bcrypt($validated['password']),
        ]);

        return redirect()->route('profile.edit')
            ->with('status', 'Senha atualizada com sucesso!')
            ->with('type', 'success');
    }

    /**
     * Deletar conta
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string|current_password',
        ]);

        $user = $request->user();

        auth()->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Conta excluída com sucesso.')
            ->with('type', 'success');
    }
}
