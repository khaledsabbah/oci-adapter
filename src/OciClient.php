<?php

declare(strict_types=1);

namespace PatrickRiemer\OciAdapter;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Hitrov\OCI\Signer;

readonly class OciClient
{
    public array $config;
    public self $client;

    public static function createWithConfiguration(array $config): self
    {
        $instance = new OciClient();

        $instance->validateConfiguration($config);

        $instance->config = $config;

        return $instance;
    }

    private function validateConfiguration(array $config): void
    {
        $amount = count(array_intersect(array_keys($config), [
            'namespace', 'region', 'bucket', 'tenancy_id', 'user_id', 'storage_tier', 'key_fingerprint', 'key_path',
        ]));
       if ($amount !== 8) {
            throw new \Exception('Invalid configuration');
        }
    }

    public function getBucket(): string
    {
        return $this->config['bucket'];
    }

    public function getBucketUri(): string
    {
        return sprintf(
            '%s/n/%s/b/%s',
            $this->getHost(),
            $this->getNamespace(),
            $this->getBucket(),
        );
    }

    public function send(string $uri, string $method, array $header = [], string $content_type = 'application/json', ?string $body = null)
    {
        $authorization_headers = $this->getAuthorizationHeaders($uri, $method, $body, $content_type);

        $client = new Client([
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);

        $request = new Request($method, $uri, array_merge($header, $authorization_headers));

        return $client->send($request);
    }

    public function getFingerprint(): string
    {
        return $this->config['key_fingerprint'];
    }

    public function getHost(): string
    {
        return sprintf('https://objectstorage.%s.oraclecloud.com', $this->getRegion());
    }

    public function getNamespace(): string
    {
        return $this->config['namespace'];
    }

    public function getPrivateKey(): string
    {
        return $this->config['key_path'];
    }

    public function getRegion(): string
    {
        return $this->config['region'];
    }

    public function getAuthorizationHeaders(string $uri, string $method, ?string $body = null, string $content_type = 'application/json'): array
    {
        $headers = [];

        $signer = new Signer($this->getTenancy(), $this->getUser(), $this->getFingerprint(), $this->getPrivateKey());

        $strings = $signer->getHeaders($uri, $method, $body, $content_type);

        foreach ($strings as $item) {
            $token = explode(': ', $item);
            $headers[ucfirst($token[0])] = trim($token[1]);
        }

        return $headers;
    }

    public function getStorageTier(): string
    {
        return $this->config['storage_tier'];
    }

    public function getTenancy(): string
    {
        return $this->config['tenancy_id'];
    }

    public function getUser(): string
    {
        return $this->config['user_id'];
    }


    public static function createPreauthenticatedRequest(string $path, \Carbon\Carbon $expires): string
    {
        // Signature: POST /n/{namespaceName}/b/{bucketName}/p/
        $access_type = 'ObjectRead';

        $body = json_encode([
            'accessType' => $access_type,
            'name' => \Ramsey\Uuid\Uuid::uuid7()->toString(),
            'objectName' => $path,
            'timeExpires' => $expires->toIso8601String(),
        ]);
    }
}