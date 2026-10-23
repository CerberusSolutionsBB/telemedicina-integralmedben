<?php

namespace App\Providers;

use App\Http\Services\ExternalApi\ClubeBeneficiosService;
use App\Http\Services\ExternalApi\SiprovExternalService;
use App\Http\Services\ExternalApi\TelemedicinaService;
use App\Notifications\NotificationDispatcher;
use App\Services\Siprov\SiprovIntegrationService;
use App\Services\SmsSenderService;
use App\Services\Tenant\TenantPublicService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationDispatcher::class, function () {
            return new NotificationDispatcher(
                app(SmsSenderService::class),
            );
        });

        $this->app->singleton(ClubeBeneficiosService::class, function () {
            return new ClubeBeneficiosService(
                baseUrl: config('external_apis.rede_parcerias.base_url'),
                clientId: config('external_apis.rede_parcerias.client_id'),
                clientSecret: config('external_apis.rede_parcerias.client_secret'),
            );
        });

        $this->app->singleton(TelemedicinaService::class, function () {
            return new TelemedicinaService(
                baseUrl: config('external_apis.telemedicina.base_url'),
                apiKey: config('external_apis.telemedicina.api_key'),
            );
        });

        $this->app->singleton(SiprovExternalService::class, function () {
            return new SiprovExternalService(
                app(SiprovIntegrationService::class),
            );
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configurarEmailRedefinicaoSenha();

        InitializeTenancyByDomain::$onFail = function ($e, $request, $next) {
            return $next($request);
        };

        // $host          = request()->getHost();
        // $current       = Tenant::with(['details'])->where('slug', $currentTenant)->first();
        Inertia::share([
            'tenant_public' => fn () => app(TenantPublicService::class)->current(),
            'authUser' => function () {
                $user = auth()->user();

                if (! $user) {
                    return [
                        'user' => null,
                        'check' => false,
                    ];
                }

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'avatar' => $user->avatar_url,
                        'created_at' => $user->created_at?->format('d/m/Y H:i'),

                        // Roles e Permissions brutas
                        'roles' => $user->getRoleNames()->toArray(),
                        'permissions' => $user->getPermissionNames()->toArray(),

                        // Verificações rápidas
                        'is_admin' => $user->hasRole('Admin'),
                        'is_manager' => $user->hasRole('Manager'),
                        'is_editor' => $user->hasRole('Editor'),
                    ],

                    // ✅ CAN - Permissões organizadas para o frontend
                    'can' => [
                        // Users
                        'users' => [
                            'view' => $user->can('users.view'),
                            'create' => $user->can('users.create'),
                            'edit' => $user->can('users.edit'),
                            'delete' => $user->can('users.delete'),
                            'manage' => $user->can('users.manage'),
                        ],

                        // Forms
                        'forms' => [
                            'view' => $user->can('forms.view'),
                            'create' => $user->can('forms.create'),
                            'edit' => $user->can('forms.edit'),
                            'delete' => $user->can('forms.delete'),
                            'update_status' => $user->can('forms.update.status'),
                            'toggle_visibility' => $user->can('forms.toggle.visibility'),
                            'manage_all' => $user->can('forms.manage.all'),
                        ],

                        // Paginas (Tenants)
                        'paginas' => [
                            'view' => $user->can('paginas.view'),
                            'create' => $user->can('paginas.create'),
                            'edit' => $user->can('paginas.edit'),
                            'delete' => $user->can('paginas.delete'),
                            'show' => $user->can('paginas.show'),
                            'manage' => $user->can('paginas.manage'),
                        ],

                        // Leis
                        'leis' => [
                            'view' => $user->can('leis.view'),
                            'create' => $user->can('leis.create'),
                            'edit' => $user->can('leis.edit'),
                            'delete' => $user->can('leis.delete'),
                        ],
                        'siprov' => [
                            'view' => $user->can('siprov.view'),
                            'create' => $user->can('siprov.create'),
                            'show' => $user->can('siprov.show'),
                            'delete' => $user->can('siprov.delete'),
                            'retry' => $user->can('siprov.retry'),
                            'manage' => $user->can('siprov.manage'),
                        ],

                        // Geral
                        'manage' => $user->hasRole('Admin') || $user->hasRole('Manager'),
                    ],

                    'check' => true,
                ];
            },

            // App info
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],

            // Flash messages
            'flash' => function () {
                return [
                    'message' => session('message'),
                    'type' => session('type'),
                ];
            },

            // CSRF
            'csrf_token' => fn () => csrf_token(),
        ]);
    }

    /**
     * Link de redefinição no domínio de quem pediu (painel central ou parceiro) e e-mail em português.
     */
    private function configurarEmailRedefinicaoSenha(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $rota = tenancy()->initialized ? 'tenant.password.reset' : 'password.reset';

            return route($rota, ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
        });

        ResetPassword::toMailUsing(function ($user, string $token) {
            $remetente = app(TenantPublicService::class)->current()['descricao'] ?? config('mail.from.name');
            // Painel do parceiro: logo dele (se cadastrado); painel central ou parceiro sem logo: MEDBEM.
            $logoParceiro = tenancy()->initialized ? tenant()?->caminhoArquivoLogo() : null;

            return (new MailMessage)
                ->subject("Redefinição de senha - {$remetente}")
                ->view(['emails.redefinir-senha', 'emails.redefinir-senha-texto'], [
                    'remetente' => $remetente,
                    'logo' => $logoParceiro ?? public_path('images/logo-med-ben-email.jpg'),
                    'nome' => $user->name,
                    'url' => (ResetPassword::$createUrlCallback)($user, $token),
                    'expira' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]);
        });
    }
}
