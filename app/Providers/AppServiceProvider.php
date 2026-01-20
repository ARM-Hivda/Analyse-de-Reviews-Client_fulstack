<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        // Fix pour MySQL : limite la longueur des strings à 191 caractères
        // pour éviter l'erreur "La clé est trop longue" avec utf8mb4
        Schema::defaultStringLength(191);
    }
}
