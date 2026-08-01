<?php

namespace JeffersonGoncalves\MetricsFathom;

use Illuminate\Support\Facades\Config;
use JeffersonGoncalves\MetricsFathom\Settings\FathomSettings;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FathomServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('metrics-fathom');
    }

    public function packageRegistered(): void
    {
        Config::set('settings.settings', array_merge(
            Config::get('settings.settings', []),
            [FathomSettings::class]
        ));

        $this->app->singleton(FathomClient::class, function () {
            $settings = app(FathomSettings::class);

            return new FathomClient(
                token: $settings->api_token,
                baseUrl: $settings->base_url,
            );
        });

        $this->app->singleton('fathom', function ($app) {
            return new Fathom($app->make(FathomClient::class));
        });

        $this->app->alias('fathom', Fathom::class);
    }

    public function packageBooted(): void
    {
        $migrationsPath = __DIR__.'/../database/settings';

        Config::set('settings.migrations_paths', array_merge(
            [$migrationsPath],
            Config::get('settings.migrations_paths', [])
        ));

        $this->publishes([
            $migrationsPath => database_path('settings'),
        ], 'metrics-fathom-settings-migrations');
    }
}
