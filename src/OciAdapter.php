<?php

namespace PatrickRiemer\OciAdapter;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Hitrov\OCI\Signer;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;

class OciAdapter implements FilesystemAdapter
{
    public function __construct(readonly private OciClient $client)
    {
    }

    public function fileExists(string $path): bool
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send($uri, 'HEAD');

            switch ($response->getStatusCode()) {
                case 200:
                    $exists = true;
                    break;

                case 404:
                    $exists = false;
                    break;

                default:
                    throw new UnableToReadFile('Invalid return code', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            if ($exception->getCode() === 404) {
                $exists = false;
            } else {
                throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        return $exists;
    }

    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $uri = sprintf('%s/o/%s', $this->getBucketUri(), urlencode($path));

        $headers = $this->getHeaders($uri, 'PUT', $contents, 'application/octet-stream');

        try {
            $response = $this->getClient()->put($uri, [
                'headers' => array_merge($headers, [
                    'storage-tier' => $this->getStorageTier(),
                ]),
                'body' => $contents,
            ]);
            if ($response->getStatusCode() === 200) {

            } else {
                ray($response)->red();
            }
        } catch (GuzzleException $exception) {
            ray($exception)->red();
            // TODO: Implement Flysystem exception handling
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        throw new \Exception('Not implemented yet.');
    }

    public function read(string $path): string
    {
        $exists = null;

        $uri = sprintf('%s/o/%s', $this->getBucketUri(), urlencode($path));

        $headers = $this->getHeaders($uri, 'GET');

        $client = $this->getClient();

        $request = new Request('GET', $uri, $headers);

        try {
            $response = $client->send($request);

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            } else if ($response->getStatusCode() === 404) {
                $exists = false;
            }
        } catch (GuzzleException $exception) {
            $exists = false;
            // TODO: Implement Flysystem exception handling
        }

        return $exists;
    }

    public function readStream(string $path)
    {
        throw new \Exception('Not implemented yet.');
    }

    public function delete(string $path): void
    {
        $uri = sprintf('%s/o/%s', $this->getBucketUri(), urlencode($path));

        $headers = $this->getHeaders($uri, 'DELETE');

        $client = $this->getClient();

        $request = new Request('DELETE', $uri, $headers);

        try {
            $response = $client->send($request);

            if ($response->getStatusCode() === 204) {

            }
        } catch (GuzzleException $exception) {
            // TODO: Implement Flysystem exception handling
        }
    }

    public function deleteDirectory(string $path): void
    {
        throw new \Exception('Not implemented yet.');
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw new \Exception('Not implemented yet.');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new \Exception('Not implemented yet.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw new \Exception('Not implemented yet.');
    }

    public function mimeType(string $path): FileAttributes
    {
        $uri = sprintf('%s/o/%s', $this->getBucketUri(), urlencode($path));

        $headers = $this->getHeaders($uri, 'HEAD');

        try {
            $response = $this->getClient()->head($uri, [
                'headers' => array_merge($headers, [
                    'Content-Type' => 'application/json',
                ]),
            ]);
            if ($response->getStatusCode() === 200) {

                $file_attributes = new FileAttributes(path: $path, mimeType: $response->getHeader('Content-Type')[0]);

                return $file_attributes;
            } else {
                ray($response)->green();
            }
        } catch (GuzzleException $exception) {
            ray($exception)->red();
            // TODO: Implement Flysystem exception handling
        }

        return new FileAttributes($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        $uri = sprintf('%s/o/%s', $this->getBucketUri(), urlencode($path));

        $headers = $this->getHeaders($uri, 'HEAD');

        try {
            $response = $this->getClient()->head($uri, [
                'headers' => array_merge($headers, [
                    'Content-Type' => 'application/json',
                ]),
            ]);
            if ($response->getStatusCode() === 200) {

                $file_attributes = new FileAttributes(path: $path, lastModified: Carbon::parse($response->getHeader('last-modified')[0])->timestamp);

                return $file_attributes;
            } else {
                ray($response)->green();
            }
        } catch (GuzzleException $exception) {
            ray($exception)->red();
            // TODO: Implement Flysystem exception handling
        }

        return new FileAttributes($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        $uri = sprintf('%s/o/%s', $this->getBucketUri(), urlencode($path));

        $headers = $this->getHeaders($uri, 'HEAD');

        try {
            $response = $this->getClient()->head($uri, [
                'headers' => array_merge($headers, [
                    'Content-Type' => 'application/json',
                ]),
            ]);
            if ($response->getStatusCode() === 200) {

                $file_attributes = new FileAttributes(path: $path, fileSize: $response->getHeader('Content-Length')[0]);

                return $file_attributes;
            } else {
                ray($response)->green();
            }
        } catch (GuzzleException $exception) {
            ray($exception)->red();
            // TODO: Implement Flysystem exception handling
        }

        return new FileAttributes($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        throw new \Exception('Not implemented yet.');
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);

        // TODO: copy only creates a copy request but does not copy the file directly.
        // That means that the delete will delete the file before it can be copied
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $uri = sprintf('%s/actions/copyObject', $this->getBucketUri());

        $body = json_encode([
            'sourceObjectName' => $source,
            'destinationRegion' => $this->getRegion(),
            'destinationNamespace' => $this->getNamespace(),
            'destinationBucket' => $this->getBucket(),
            'destinationObjectName' => $destination,
        ]);

        $headers = $this->getHeaders($uri, 'POST', $body);

        try {
            $response = $this->getClient()->post($uri, [
                'headers' => array_merge($headers, []),
                'body' => $body
            ]);
            if ($response->getStatusCode() === 202) {
                ray('successfully copied');
            } else {
                ray($response)->red();
            }
        } catch (GuzzleException $exception) {
            ray($exception)->red();
            // TODO: Implement Flysystem exception handling
        }
    }
}