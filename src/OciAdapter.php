<?php

namespace KhaledGamal\OciAdapter;

use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

readonly class OciAdapter implements FilesystemAdapter
{
    public function __construct(protected OciClient $client)
    {
    }

	/**
	 * @return OciClient
	 */
	public function getClient(): OciClient
	{
		return $this->client;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getUrl(string $path): string
	{
		return sprintf('%s/o/%s', $this->client->getBucketUri(), urlencode($path));
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

        $body = stream_get_contents($contents);

        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $body);

        try {
            $response = $this->client->send(
                $uri,
                'PUT',
                ['storage-tier' => $this->client->getStorageTier()],
                $body,
                $mime,
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
        throw new \Exception('Adapter does not support read stream.');
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
        $this->delete($path . '/');
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->write($path . '/', '', $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new \Exception('Adapter does not support visibility.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw new \Exception('Adapter does not support visibility.');
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
        $files = collect([]);

        $uri = sprintf('%s/o', $this->client->getBucketUri());

        try {
            $response = $this->client->send($uri, 'GET');

            switch ($response->getStatusCode()) {
                case 200:

                    $data = json_decode($response->getBody()->getContents());

                    foreach ($data->objects as $object) {
                        $files->add(new FileAttributes($object->name));
                    }

                    break;

                default:
                    throw new UnableToReadFile('Unable to read file', $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new UnableToReadFile($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $files;
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