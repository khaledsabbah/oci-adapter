<?php

namespace KhaledGamal\OciAdapter;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

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
            $adapter = new OciAdapter($client);

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
