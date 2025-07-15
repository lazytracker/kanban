<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;

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
        // Настройка перенаправлений после авторизации
        $this->configureRedirects();
    }

    /**
     * Configure authentication redirects.
     */
    private function configureRedirects(): void
    {
        // После логина перенаправляем на kanban
        config(['auth.redirects.login' => '/']);
        
        // После регистрации перенаправляем на kanban
        config(['auth.redirects.register' => '/']);
        
        // После подтверждения email перенаправляем на kanban
        config(['auth.redirects.email-verification' => '/']);
        
        // После сброса пароля перенаправляем на kanban
        config(['auth.redirects.password-reset' => '/']);
    }
}