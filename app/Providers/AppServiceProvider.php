<?php

namespace App\Providers;

use App\Services\EmailDeliveryLogger;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', AppleProvider::class);
        });

        Event::listen(MessageSending::class, [EmailDeliveryLogger::class, 'recordSending']);
        Event::listen(MessageSent::class, [EmailDeliveryLogger::class, 'recordSent']);

        $appUrl = config('app.url');

        if (is_string($appUrl) && $appUrl !== '') {
            URL::forceRootUrl(rtrim($appUrl, '/'));

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
