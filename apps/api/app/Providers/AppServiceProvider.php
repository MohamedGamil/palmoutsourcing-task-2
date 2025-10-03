<?php

namespace App\Providers;

use App\Services\ProxyService;
use Domain\Product\Service\ProxyServiceInterface;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // REQ-ARCH-015: Service providers SHALL configure app layer dependencies
        // REQ-INT-001: Backend SHALL communicate with Golang proxy service
        $this->app->bind(ProxyServiceInterface::class, function ($app) {
            return new ProxyService(
                $app->make(HttpClient::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
