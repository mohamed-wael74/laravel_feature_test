<?php

namespace Solutionplus\FeatureTest;

use Illuminate\Support\ServiceProvider;

class SolutionplusFeatureTestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->autoPublishConfig();

        $this->publishes([
            __DIR__ . '/config/feature_test.php' => config_path('feature_test.php'),
        ]);
    }

    /**
     * Auto-publish the config file if it doesn't exist
     */
    private function autoPublishConfig()
    {
        $configPath = config_path('feature_test.php');

        if (!file_exists($configPath) && $this->app->runningInConsole()) {
            copy(__DIR__ . '/config/feature_test.php', $configPath);
        }
    }
}
