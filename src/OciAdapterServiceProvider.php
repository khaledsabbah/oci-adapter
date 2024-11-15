<?php

namespace PatrickRiemer\OciAdapter;

use Carbon\Carbon;
use DateTimeInterface;
use GuzzleHttp\Psr7\Request;
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
        })->buildTemporaryUrlsUsing(function (string $path, \DateTimeInterface $expiresAt, array $options): string {
            $config = [
                'namespace' => config('filesystems.disks.oci.namespace'),
                'region' => config('filesystems.disks.oci.region'),
                'bucket' => config('filesystems.disks.oci.bucket'),
                'tenancy_id' => config('filesystems.disks.oci.tenancy_id'),
                'user_id' => config('filesystems.disks.oci.user_id'),
                'storage_tier' => config('filesystems.disks.oci.storage_tier'),
                'key_fingerprint' => config('filesystems.disks.oci.key_fingerprint'),
                'key_path' => config('filesystems.disks.oci.key_path'),
            ];

            $client = OciClient::createWithConfiguration($config);

            return $client->createTemporaryUrl($path, Carbon::instance($expiresAt));
        });
    }
}
