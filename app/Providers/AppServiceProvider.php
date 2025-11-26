<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\PictureServiceInterface::class,
            \App\Services\PictureService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
