<?php

declare(strict_types=1);

use App\Http\Controllers\PatientCartaoDinamicoController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\Tenant\Configuracao\ConfiguracaoController;
use App\Http\Controllers\Tenant\ConvenioController;
use App\Http\Controllers\Tenant\CupomBeneficioController;
use App\Http\Controllers\Tenant\CupomController as TenantCupomController;
use App\Http\Controllers\Tenant\Dashboard\DashboardController;
use App\Http\Controllers\Tenant\DesempenhoController;
use App\Http\Controllers\Tenant\Form\FormIndexController;
use App\Http\Controllers\Tenant\Form\FormShowController;
use App\Http\Controllers\Tenant\Relatorio\RelatorioProducaoController;
use App\Http\Controllers\Tenant\ServicoController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', function () {

        if (Auth::check()) {
            return redirect()->route('patients.index');
        }

        return redirect()->route('tenant.login');
    });

    Route::get('/public-form', [PublicFormController::class, 'showLoginForm'])->name('public_form.store');
    Route::get('/admin/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/admin/login', [TenantAuthController::class, 'login']);

    Route::middleware('auth')->group(function () {
        Route::post('/admin/logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');

        // Dashboard do parceiro (/dashboard e o nome 'dashboard' são do painel central).
        Route::get('/painel', DashboardController::class)->name('tenant.dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/create', [PatientController::class, 'create'])->middleware('beneficiario:create')->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->middleware('beneficiario:create')->name('patients.store');
        Route::get('/patients/report', [PatientController::class, 'reportPdf'])->name('patients.report');
        Route::get('/patients/export/{format}', [PatientController::class, 'export'])->name('patients.export')->where('format', 'csv|xlsx');
        Route::get('/patients/template/{format}', [PatientController::class, 'template'])->middleware('beneficiario:create')->name('patients.template')->where('format', 'csv|xlsx');
        Route::post('/patients/import', [PatientController::class, 'import'])->middleware('beneficiario:create')->name('patients.import');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->middleware('beneficiario:edit')->name('patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->middleware('beneficiario:edit')->name('patients.update');
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->middleware('beneficiario:delete')->name('patients.destroy');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::get('/patients/{patient}/pdf', [PatientController::class, 'downloadPdf'])->name('patients.pdf');
        Route::get('/patients/{patient}/cartao', [PatientController::class, 'cartao'])->name('patients.cartao');
        Route::get('/patients/{patient}/cartao-dinamico', [PatientCartaoDinamicoController::class, 'gerar'])->name('patients.cartao-dinamico');
        Route::post('/patients/{patient}/resend-sms', [PatientController::class, 'resendSms'])->name('patients.resend-sms');
        Route::post('/patients/{patient}/sms-logs/{smsLog}/resend', [PatientController::class, 'resendSmsLog'])->name('patients.sms-logs.resend');
        Route::patch('/patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])->middleware('beneficiario:status')->name('patients.toggle-status');

        // Serviços oferecidos pelo parceiro (auditados).
        Route::resource('servicos', ServicoController::class)->parameters(['servicos' => 'id'])->whereNumber('id');
        Route::put('servicos/{id}/status', [ServicoController::class, 'status'])->whereNumber('id')->name('servicos.status');

        // Convênios do parceiro (auditados).
        Route::resource('convenios', ConvenioController::class)->parameters(['convenios' => 'id'])->whereNumber('id');
        Route::put('convenios/{id}/status', [ConvenioController::class, 'status'])->whereNumber('id')->name('convenios.status');

        // Cupons de benefício (desconto em convênio/serviço; banco do tenant, auditados).
        Route::resource('cupons-beneficio', CupomBeneficioController::class)->parameters(['cupons-beneficio' => 'id'])->whereNumber('id');
        Route::put('cupons-beneficio/{id}/status', [CupomBeneficioController::class, 'status'])->whereNumber('id')->name('cupons-beneficio.status');
        Route::get('cupons-beneficio/{id}/pdf', [CupomBeneficioController::class, 'pdf'])->whereNumber('id')->name('cupons-beneficio.pdf');

        // Cupons do próprio parceiro: só com teto de desconto definido pelo admin (checado no controller).
        Route::prefix('cupons')->name('cupons.')->controller(TenantCupomController::class)->group(function () {
            // Prévia do cupom no cadastro do beneficiário (vale também para cupons globais, sem teto).
            Route::post('/validar', 'validar')->middleware('throttle:20,1')->name('validar');
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{cupom}', 'show')->whereNumber('cupom')->name('show');
            Route::get('/{cupom}/edit', 'edit')->whereNumber('cupom')->name('edit');
            Route::put('/{cupom}', 'update')->whereNumber('cupom')->name('update');
            Route::put('/{cupom}/status', 'status')->whereNumber('cupom')->name('status');
            Route::get('/{cupom}/pdf', 'pdf')->whereNumber('cupom')->name('pdf');
            Route::delete('/{cupom}', 'destroy')->whereNumber('cupom')->name('destroy');
        });

        Route::prefix('admin/acl')->name('tenant.acl.')->group(base_path('routes/acl.php'));

        // Metas de desempenho dos usuários (acesso: qualquer usuário logado).
        Route::get('desempenho/usuarios', [DesempenhoController::class, 'usuarios'])->name('desempenho.usuarios');
        Route::resource('desempenho', DesempenhoController::class)->parameters(['desempenho' => 'desempenho']);

        // Relatórios da tela Relatório (produção por usuário).
        Route::get('/relatorios/producao/{formato}', RelatorioProducaoController::class)
            ->whereIn('formato', ['pdf', 'xlsx'])
            ->name('relatorios.producao');

        Route::prefix('meus-formularios')->name('meus-formularios.')->group(function () {
            Route::get('/', FormIndexController::class)->name('index');
            Route::get('/{form}', FormShowController::class)->name('show');
        });

        Route::prefix('configuracao')
            ->name('configuracao.')
            ->controller(ConfiguracaoController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/logo', [ConfiguracaoController::class, 'updateLogo'])->name('logo.update');
                Route::put('/cartao-dinamico/cores', 'updateCartaoDinamicoCores')->name('cartao-dinamico.cores');
                Route::put('/cartao-dinamico/toggle', 'toggleCartaoDinamico')->name('cartao-dinamico.toggle');
                Route::put('/cartao-dinamico/qrcode', 'updateCartaoQrCode')->name('cartao-dinamico.qrcode');
                Route::post('/cartao-dinamico/{tipo}', 'storeCartaoDinamicoImagem')
                    ->whereIn('tipo', ['logo', 'frente', 'verso'])
                    ->name('cartao-dinamico.imagem.store');
                Route::delete('/cartao-dinamico/{tipo}', 'destroyCartaoDinamicoImagem')
                    ->whereIn('tipo', ['logo', 'frente', 'verso'])
                    ->name('cartao-dinamico.imagem.destroy');
            });
    });
});
