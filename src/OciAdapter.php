<?php

namespace PatrickRiemer\OciAdapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Hitrov\OCI\Signer;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class OciAdapter implements FilesystemAdapter
{
    public function __construct(readonly private array $configuration)
    {
    }

    public function fileExists(string $path): bool
    {
        $exists = null;

        $uri = sprintf('%s/o/%s', $this->getBucketUri(), $path);

        $headers = $this->getHeaders($uri, 'HEAD');

        try {
            $response = $this->getClient()->head($uri, $headers);

            if ($response->getStatusCode() === 200) {
                $exists = true;
            } else if ($response->getStatusCode() === 404) {
                $exists = false;
            }
        } catch (GuzzleException $exception) {
            // TODO: Implement Flysystem exception handling
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

    private function getTenancy(): string
    {
        return $this->configuration['tenancy'];
    }

    private function getUser(): string
    {
        return $this->configuration['user'];
    }

    private function getFingerprint(): string
    {
        return $this->configuration['fingerprint'];
    }

    private function getPrivateKey(): string
    {
        return $this->configuration['path'];
    }

    private function getHost(): string
    {
        return sprintf('https://objectstorage.%s.oraclecloud.com', $this->getRegion());
    }

    private function getClient(): Client
    {
        return new Client([
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);
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

    private function getHeaders(string $uri, string $method, ?string $body = null): array
    {
        $headers = [];

        $signer = new Signer($this->getTenancy(), $this->getUser(), $this->getFingerprint(), $this->getPrivateKey());

        $strings = $signer->getHeaders($uri, $method, $body, 'application/json');

        foreach ($strings as $item) {
            $token = explode(': ', $item);
            $headers[$token[0]] = $token[1];
        }

        return $headers;
    }
}