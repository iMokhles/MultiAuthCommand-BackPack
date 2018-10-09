<?php

namespace iMokhles\MultiAuthBackPack;

use Illuminate\Support\ServiceProvider;

class MultiAuthBackPackServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->registerInstallCommand();
    }

    /**
     * Register the make:multi-auth command.
     */
    private function registerInstallCommand()
    {
        $this->app->singleton('command.imokhles.make.multi-backpack', function ($app) {
            return $app['iMokhles\MultiAuthBackPack\Command\MultiAuthPrepare'];
        });
        $this->commands('command.imokhles.make.multi-backpack');
    }
}
