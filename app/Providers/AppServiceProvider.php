<?php
namespace App\Providers;

use App\Events\PatientCreated;
use App\Http\Services\ExternalApi\ClubeBeneficiosService;
use App\Http\Services\ExternalApi\TelemedicinaService;
use App\Listeners\RegisterPatientToExternalApis;
use App\Listeners\SendNotificationOnPatientCreated;
use App\Listeners\SyncPatientToCentral;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\NotificationDispatcher;
use App\Services\Auth\CentralAuthContextService;
use App\Services\Auth\TenantAuthContextService;
use App\Services\Tenant\TenantPublicService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationDispatcher::class, function () {
            return new NotificationDispatcher([
                app(SmsChannel::class),
            ]);
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

        $this->app->singleton(CentralAuthContextService::class);
        $this->app->singleton(TenantAuthContextService::class);
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(PatientCreated::class, SyncPatientToCentral::class);
        Event::listen(PatientCreated::class, SendNotificationOnPatientCreated::class);
        Event::listen(PatientCreated::class, RegisterPatientToExternalApis::class);

        Inertia::share([
            'tenant_public' => fn() => app(TenantPublicService::class)->current(),

            'authUser'      => fn()      => tenant()
                ? app(TenantAuthContextService::class)->current()
                : app(CentralAuthContextService::class)->current(),

            'app'           => [
                'name' => config('app.name'),
                'env'  => config('app.env'),
                'url'  => config('app.url'),
            ],

            'flash'         => fn()         => [
                'message' => session('message'),
                'type'    => session('type'),
            ],

            'csrf_token'    => fn()    => csrf_token(),
        ]);
    }
}
