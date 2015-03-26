<?php namespace Ingruz\Rest;

use Illuminate\Support\ServiceProvider;

class RestServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/config.php';
        $this->mergeConfigFrom($configPath, 'ingruzrest');

        $this->publishes([
            $configPath => config_path('ingruzrest.php')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

}
