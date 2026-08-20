<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'forms.delete', 'guard_name' => 'web']);
    }

    public function test_admin_can_delete_form_with_responses(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('forms.delete');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $form = Form::factory()->create();
        FormResponse::create(['form_id' => $form->id, 'answers' => ['q1' => 'resposta 1']]);
        FormResponse::create(['form_id' => $form->id, 'answers' => ['q1' => 'resposta 2']]);

        $this->assertSame(2, $form->responses()->count());

        $response = $this->actingAs($admin)->delete(route('forms.destroy', $form->id));

        $response->assertRedirect(route('forms.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('forms', ['id' => $form->id]);
        $this->assertSame(0, FormResponse::where('form_id', $form->id)->count());
        $this->assertDatabaseCount('form_responses', 2); // continuam no banco, só soft-deleted
    }

    public function test_regular_user_cannot_delete_form_with_responses(): void
    {
        $userRole = Role::create(['name' => 'User', 'guard_name' => 'web']);
        $userRole->givePermissionTo('forms.delete');

        $user = User::factory()->create();
        $user->assignRole('User');

        $form = Form::factory()->create(['user_id' => $user->id]);
        FormResponse::create(['form_id' => $form->id, 'answers' => ['q1' => 'resposta 1']]);

        $response = $this->actingAs($user)->delete(route('forms.destroy', $form->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Este formulário possui respostas e não pode ser excluído.');

        $this->assertDatabaseHas('forms', ['id' => $form->id, 'deleted_at' => null]);
        $this->assertSame(1, FormResponse::where('form_id', $form->id)->count());
    }
}
