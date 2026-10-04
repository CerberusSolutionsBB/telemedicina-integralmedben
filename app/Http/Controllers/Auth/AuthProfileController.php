<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        return redirect()->route('perfil.edit')
            ->with('status', 'Perfil atualizado com sucesso!')
            ->with('type', 'success');
    }

    /**
     * Foto de perfil do usuário logado (disco 'local', separado por tenant).
     */
    public function showFoto(Request $request)
    {
        $path = $request->user()->avatar_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=31536000',
        ]);
    }

    /**
     * Enviar/trocar foto de perfil
     */
    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'foto.required' => 'Selecione uma foto.',
            'foto.image' => 'O arquivo precisa ser uma imagem.',
            'foto.mimes' => 'Use uma imagem JPG, PNG ou WEBP.',
            'foto.max' => 'A foto deve ter no máximo 2 MB.',
        ]);

        $user = $request->user();
        $anterior = $user->avatar_path;

        $user->update(['avatar_path' => $request->file('foto')->store('avatars', 'local')]);

        if ($anterior) {
            Storage::disk('local')->delete($anterior);
        }

        return redirect()->route('perfil.edit')
            ->with('status', 'Foto atualizada com sucesso!')
            ->with('type', 'success');
    }

    /**
     * Remover foto de perfil
     */
    public function destroyFoto(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return redirect()->route('perfil.edit')
            ->with('status', 'Foto removida.')
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

        return redirect()->route('perfil.edit')
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

        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Conta excluída com sucesso.')
            ->with('type', 'success');
    }
}
