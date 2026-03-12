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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
                baseUrl:      config('external_apis.rede_parcerias.base_url'),
                clientId:     config('external_apis.rede_parcerias.client_id'),
                clientSecret: config('external_apis.rede_parcerias.client_secret'),
            );
        });

        $this->app->singleton(TelemedicinaService::class, function () {
            return new TelemedicinaService(
                baseUrl: config('external_apis.telemedicina.base_url'),
                apiKey:  config('external_apis.telemedicina.api_key'),
            );
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(PatientCreated::class, SyncPatientToCentral::class);
        Event::listen(PatientCreated::class, SendNotificationOnPatientCreated::class);
        Event::listen(PatientCreated::class, RegisterPatientToExternalApis::class);
    }
}
