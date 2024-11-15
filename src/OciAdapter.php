<?php

namespace PatrickRiemer\OciAdapter;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\RequestOptions;
use Hitrov\OCI\Signer;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

readonly class OciAdapter implements FilesystemAdapter
{
    public function __construct(private OciClient $client)
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

    public function write(string $path, string $contents, Config $config): void
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send(
                $uri,
                'PUT',
                ['storage-tier' => $this->client->getStorageTier()],
                $contents,
                'application/octet-stream'
            );

            if ($response->getStatusCode() !== 200) {
                throw new UnableToWriteFile('Unable to write file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToWriteFile($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        $tempStream = fopen('php://temp', 'r+');
        stream_copy_to_stream($contents, $tempStream);
        rewind($tempStream);
        $body = stream_get_contents($tempStream);
        fclose($tempStream);

        try {
            $response = $this->client->send(
                $uri,
                'PUT',
                ['storage-tier' => $this->client->getStorageTier()],
                $body,
                'image/png'
            );

            if ($response->getStatusCode() !== 200) {
                throw new UnableToWriteFile('Unable to write file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToWriteFile($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function read(string $path): string
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send($uri, 'GET');

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            } else {
                throw new UnableToReadFile('Unable to read file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function readStream(string $path)
    {
        throw new \Exception('Not implemented yet.');
    }

    public function delete(string $path): void
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send($uri, 'DELETE');

            switch ($response->getStatusCode()) {
                case 204:
                    break;

                default:
                    throw new UnableToReadFile('Unable to read file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
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
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send($uri, 'HEAD');

            switch ($response->getStatusCode()) {
                case 200:
                    $file_attributes = new FileAttributes(path: $path, mimeType: $response->getHeader('Content-Type')[0]);
                    break;

                default:
                    throw new UnableToReadFile('Unable to read file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $file_attributes;
    }

    public function lastModified(string $path): FileAttributes
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send($uri, 'HEAD');

            switch ($response->getStatusCode()) {
                case 200:
                    $file_attributes = new FileAttributes(path: $path, lastModified: Carbon::parse($response->getHeader('last-modified')[0])->timestamp);
                    break;

                default:
                    throw new UnableToReadFile('Unable to read file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $file_attributes;
    }

    public function fileSize(string $path): FileAttributes
    {
        $uri = sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));

        try {
            $response = $this->client->send($uri, 'HEAD');

            switch ($response->getStatusCode()) {
                case 200:
                    $file_attributes = new FileAttributes(path: $path, fileSize: $response->getHeader('Content-Length')[0]);
                    break;

                default:
                    throw new UnableToReadFile('Unable to read file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $file_attributes;
    }

    public function listContents(string $path, bool $deep): iterable
    {
        throw new \Exception('Not implemented yet.');
    }

    public function move(string $source, string $destination, Config $config): void
    {
        // TODO: copy only creates a copy request but does not copy the file directly.
        // That means that the delete will delete the file before it can be copied

        $uri = sprintf('%s/actions/copyObject', $this->client->getBucketUri());

        $body = json_encode([
            'sourceObjectName' => $source,
            'destinationRegion' => $this->client->getRegion(),
            'destinationNamespace' => $this->client->getNamespace(),
            'destinationBucket' => $this->client->getBucket(),
            'destinationObjectName' => $destination,
        ]);

        try {
            $this->client->send($uri, 'POST', [], $body);
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $uri = sprintf('%s/actions/copyObject', $this->client->getBucketUri());

        $body = json_encode([
            'sourceObjectName' => $source,
            'destinationRegion' => $this->client->getRegion(),
            'destinationNamespace' => $this->client->getNamespace(),
            'destinationBucket' => $this->client->getBucket(),
            'destinationObjectName' => $destination,
        ]);

        try {
            $this->client->send($uri, 'POST', [], $body);
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}