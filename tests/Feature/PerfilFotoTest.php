<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilFotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_troca_e_remove_foto(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('perfil.foto.update'), ['foto' => UploadedFile::fake()->image('a.jpg')])
            ->assertRedirect(route('perfil.edit'));

        $primeira = $user->fresh()->avatar_path;
        Storage::disk('local')->assertExists($primeira);
        $this->get(route('perfil.foto.show'))->assertOk();

        $this->post(route('perfil.foto.update'), ['foto' => UploadedFile::fake()->image('b.png')]);
        $segunda = $user->fresh()->avatar_path;
        Storage::disk('local')->assertMissing($primeira);
        Storage::disk('local')->assertExists($segunda);

        $this->delete(route('perfil.foto.destroy'))->assertRedirect(route('perfil.edit'));
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($segunda);
        $this->get(route('perfil.foto.show'))->assertNotFound();
    }

    public function test_rejeita_arquivo_que_nao_e_imagem_ou_grande_demais(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('perfil.foto.update'), ['foto' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('foto');

        $this->post(route('perfil.foto.update'), ['foto' => UploadedFile::fake()->image('a.jpg')->size(3000)])
            ->assertSessionHasErrors('foto');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_perfil_no_dominio_central_usa_layout_central(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Profile')->where('isTenant', false));
    }

    public function test_exige_autenticacao(): void
    {
        $this->get(route('perfil.foto.show'))->assertRedirect(route('login'));
    }
}
