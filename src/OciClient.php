<?php

declare(strict_types=1);

namespace PatrickRiemer\OciAdapter;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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

    public function send(string $uri, string $method, array $header = [], ?string $body = null, ?string $content_type = 'application/json')
    {
        $authorization_headers = $this->getAuthorizationHeaders($uri, $method, $body, $content_type);

        $client = new Client([
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);

        $request = new Request($method, $uri, array_merge($header, $authorization_headers), $body);

        return $client->send($request);
    }

    public function createTemporaryUrl(string $path, Carbon $expires_at): string
    {
        $uri = sprintf('%s/p/', $this->getBucketUri());

        $body = json_encode([
            'accessType' => 'ObjectRead',
            'name' => \Ramsey\Uuid\Uuid::uuid7()->toString(),
            'objectName' => $path,
            'timeExpires' => $expires_at->toIso8601String(),
        ]);

        try {
            $response = $this->send($uri, 'POST', [], $body);

            if ($response->getStatusCode() === 200) {
                $pre_authenticated_request = json_decode($response->getBody()->getContents());

                $temporary_url = $pre_authenticated_request->fullPath;
            }

        } catch (GuzzleException $exception) {

        }

        return $temporary_url;
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
}