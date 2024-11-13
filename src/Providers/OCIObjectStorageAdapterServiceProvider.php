<?php

namespace PatrickRiemer\OCIObjectStorageAdapter\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use PatrickRiemer\OCIObjectStorageAdapter\OCIObjectStorageAdapter;

class OneDriveAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'oci');
        $this->mergeConfigFrom(__DIR__.'/../config/filesystem.php', 'filesystem');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('oci', function(Application $app, array $config) {

            $adapter = new OCIObjectStorageAdapter($config);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config,
            );
        });
    }
}