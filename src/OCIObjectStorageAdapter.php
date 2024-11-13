<?php

namespace PatrickRiemer\OCIObjectStorageAdapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class OCIObjectStorageAdapter implements FilesystemAdapter
{
    public function __construct(private array $configuration)
    {
    }

    public function fileExists(string $path): bool
    {
        $exists = false;

        $uri = sprintf('%s/o/%s', $this->getBucketUri(), $path);

        try {
            $response = $this->getClient()->request('HEAD', $uri);

            if ($response->getStatusCode() === 200) {
                $exists = true;
            }
        } catch (GuzzleException $exception) {
            // TODO: Implement flysystem exception handling, refer to documentation
        }

        return $exists;
    }

    public function directoryExists(string $path): bool
    {
        // TODO: Implement directoryExists() method.
    }

    public function write(string $path, string $contents, Config $config): void
    {
        // TODO: PutObject
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        // TODO: PutObject
    }

    public function read(string $path): string
    {
        // TODO: GetObject
    }

    public function readStream(string $path)
    {
        // TODO: GetObject
    }

    public function delete(string $path): void
    {
        // TODO: DeleteObject
    }

    public function deleteDirectory(string $path): void
    {
        // TODO: Implement deleteDirectory() method.
    }

    public function createDirectory(string $path, Config $config): void
    {
        // TODO: Implement createDirectory() method.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // TODO: Implement setVisibility() method.
    }

    public function visibility(string $path): FileAttributes
    {
        // TODO: Implement visibility() method.
    }

    public function mimeType(string $path): FileAttributes
    {
        // TODO: HeadObject
    }

    public function lastModified(string $path): FileAttributes
    {
        // TODO: HeadObject
    }

    public function fileSize(string $path): FileAttributes
    {
        // TODO: HeadObject
    }

    public function listContents(string $path, bool $deep): iterable
    {
        // TODO: ListObjects
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // TODO: CopyObject, DeleteObject
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        // TODO: CopyObject
    }

    private function getNamespace(): string
    {
        return $this->configuration['namespace'];
    }

    private function getBucket(): string
    {
        return $this->configuration['bucket'];
    }

    private function getRegion(): string
    {
        return $this->configuration['region'];
    }

    private function getHost(): string
    {
        return sprintf('objectstorage.%s.oraclecloud.com', $this->getRegion());
    }

    private function getBucketUri(): string
    {
        return sprintf(
            '%s/n/%s/b/%s',
            $this->getHost(),
            $this->getNamespace(),
            $this->getBucket(),
        );
    }

    private function getClient(): Client
    {
        return new Client([
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);
    }
}