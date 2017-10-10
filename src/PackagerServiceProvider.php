<?php

namespace MichaelJBerry\Packager;

use Illuminate\Support\ServiceProvider;

/**
 * This is the service provider.
 *
 * Place the line below in the providers array inside app/config/app.php
 * <code>'MichaelJBerry\Packager\PackagerServiceProvider',</code>
 *
 * @package Packager
 * @author MichaelJBerry
 * 
 **/
class PackagerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The console commands.
     *
     * @var bool
     */
    protected $commands = [
        'MichaelJBerry\Packager\PackagerNewCommand',
        'MichaelJBerry\Packager\PackagerRemoveCommand',
        'MichaelJBerry\Packager\PackagerGetCommand',
        'MichaelJBerry\Packager\PackagerGitCommand',
        'MichaelJBerry\Packager\PackagerListCommand',
        'MichaelJBerry\Packager\PackagerTestsCommand',
        'MichaelJBerry\Packager\PackagerCheckCommand',
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/packager.php' => config_path('packager.php'),
        ]);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['packager'];
    }
}
