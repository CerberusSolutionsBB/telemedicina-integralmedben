<?php

namespace App\Providers;

use App\Events\PatientCreated;
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
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(PatientCreated::class, SyncPatientToCentral::class);
        Event::listen(PatientCreated::class, SendNotificationOnPatientCreated::class);
    }
}
