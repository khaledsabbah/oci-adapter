<?php

namespace PatrickRiemer\OciAdapter;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use PatrickRiemer\OciAdapter;

class OciAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'filesystems.disks');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('oci', function ($app, $config) {

            $client = OciClient::createWithConfiguration($config);
            $adapter = new OciAdapter\OciAdapter($client);

            return new FilesystemAdapter(new Filesystem(
                adapter: $adapter,
            ), $adapter, $config);
        })->buildTemporaryUrlsUsing(function (string $path, \DateTimeInterface $expiresAt, array $config): string {
            // TODO: Implement temporaryUrl() method.
            return "https://ui-avatars.com/api/?name=theodor&color=7F9CF5&background=EBF4FF";
        });
    }
}
