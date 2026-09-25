<?php

/*
|--------------------------------------------------------------------------
| ACL — Usuários, Perfis e Permissões
|--------------------------------------------------------------------------
|
| Carregado pelo central (prefixo /acl, nomes acl.*) e pelos tenants
| (prefixo /admin/acl, nomes tenant.acl.*). Cada contexto gerencia o
| próprio banco de dados.
|
*/

use App\Http\Controllers\Acl\PermissionController;
use App\Http\Controllers\Acl\RoleController;
use App\Http\Controllers\Acl\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('usuarios')->name('users.')->controller(UserController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission:acl.users.view')->name('index');
    Route::get('/create', 'create')->middleware('permission:acl.users.create')->name('create');
    Route::post('/', 'store')->middleware('permission:acl.users.create')->name('store');
    Route::get('/{user}/edit', 'edit')->middleware('permission:acl.users.edit')->name('edit');
    Route::put('/{user}', 'update')->middleware('permission:acl.users.edit')->name('update');
    Route::delete('/{user}', 'destroy')->middleware('permission:acl.users.delete')->name('destroy');
});

Route::prefix('perfis')->name('roles.')->controller(RoleController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission:acl.roles.view')->name('index');
    Route::get('/create', 'create')->middleware('permission:acl.roles.create')->name('create');
    Route::post('/', 'store')->middleware('permission:acl.roles.create')->name('store');
    Route::get('/{role}/edit', 'edit')->middleware('permission:acl.roles.edit')->name('edit');
    Route::put('/{role}', 'update')->middleware('permission:acl.roles.edit')->name('update');
    Route::delete('/{role}', 'destroy')->middleware('permission:acl.roles.delete')->name('destroy');
});

Route::prefix('permissoes')->name('permissions.')->controller(PermissionController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission:acl.permissions.view')->name('index');
    Route::post('/', 'store')->middleware('permission:acl.permissions.create')->name('store');
    Route::put('/{permission}', 'update')->middleware('permission:acl.permissions.edit')->name('update');
    Route::delete('/{permission}', 'destroy')->middleware('permission:acl.permissions.delete')->name('destroy');
});
