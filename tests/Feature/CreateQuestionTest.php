<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('cria uma question', function () {
    $response = $this->actingAs($this->user)->post(route('questions.store'), [
        'title' => 'Nome Completo',
        'type' => 'text',
    ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('questions', [
        'title' => 'Nome Completo',
        'type' => 'text',
    ]);
});
