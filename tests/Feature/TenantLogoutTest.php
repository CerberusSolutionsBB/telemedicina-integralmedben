<?php

namespace Tests\Feature;

use App\Http\Controllers\Tenant\TenantAuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sair_do_painel_do_parceiro_volta_para_o_login(): void
    {
        $this->actingAs(User::factory()->create());

        $request = Request::create('/admin/logout', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        $response = (new TenantAuthController)->logout($request);

        // Antes redirecionava para tenant.logout (só POST) e o navegador recebia 405.
        $this->assertSame(route('tenant.login'), $response->getTargetUrl());
        $this->assertGuest();
    }
}
