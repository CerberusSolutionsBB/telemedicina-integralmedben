<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaginaCorTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-cor']));
        $this->user = User::factory()->create();
    }

    private function cor(): ?string
    {
        return DB::table('tenants')->where('id', $this->tenant->id)->value('indicativo_cor');
    }

    public function test_define_e_volta_para_cor_padrao(): void
    {
        $this->actingAs($this->user)
            ->put(route('pagina.cor', $this->tenant->id), ['indicativo_cor' => '#8b5cf6'])
            ->assertSessionHas('success');

        $this->assertSame('#8B5CF6', $this->cor());
        $this->assertNull(DB::table('tenants')->where('id', $this->tenant->id)->value('data->indicativo_cor'));

        $this->put(route('pagina.cor', $this->tenant->id), ['indicativo_cor' => null]);

        $this->assertNull($this->cor());
    }

    public function test_rejeita_cor_invalida(): void
    {
        $this->actingAs($this->user)
            ->put(route('pagina.cor', $this->tenant->id), ['indicativo_cor' => 'azul'])
            ->assertSessionHasErrors('indicativo_cor');

        $this->assertNull($this->cor());
    }

    public function test_exige_autenticacao(): void
    {
        $this->put(route('pagina.cor', $this->tenant->id), ['indicativo_cor' => '#000000'])
            ->assertRedirect(route('login'));
    }
}
