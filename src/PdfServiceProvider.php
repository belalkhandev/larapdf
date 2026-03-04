<?php

declare(strict_types=1);

namespace Belal\LaraPdf;

use Belal\LaraPdf\Drivers\BrowsershotDriver;
use Belal\LaraPdf\Support\FontManager;
use Belal\LaraPdf\Support\TailwindInjector;
use Belal\LaraPdf\Support\ViewRenderer;
use Illuminate\Support\ServiceProvider;

class PdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pdf.php', 'pdf');

        $this->app->singleton(FontManager::class, function () {
            return new FontManager();
        });

        $this->app->singleton(TailwindInjector::class, function () {
            return new TailwindInjector();
        });

        $this->app->singleton(ViewRenderer::class, function ($app) {
            return new ViewRenderer($app['view']);
        });

        $this->app->singleton(BrowsershotDriver::class, function ($app) {
            return new BrowsershotDriver($app['config']['pdf']);
        });

        $this->app->bind('larapdf', function ($app) {
            return new Pdf(
                $app->make(BrowsershotDriver::class),
                $app->make(ViewRenderer::class),
                $app->make(FontManager::class),
                $app->make(TailwindInjector::class),
                $app->make(\Illuminate\Contracts\Filesystem\Factory::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pdf.php' => config_path('pdf.php'),
            ], 'belal-larapdf-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/larapdf'),
            ], 'belal-larapdf-views');

            $this->commands([
                Commands\InstallChromeCommand::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larapdf');
    }
}
