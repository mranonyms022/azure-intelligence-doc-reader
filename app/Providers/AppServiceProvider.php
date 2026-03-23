<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Azure\AzureDocumentIntelligenceService;
use App\Services\InvoiceScannerService;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AzureDocumentIntelligenceService::class);

        $this->app->singleton(InvoiceScannerService::class, function ($app) {
            return new InvoiceScannerService(
                $app->make(AzureDocumentIntelligenceService::class)
            );
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
