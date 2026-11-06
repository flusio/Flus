<?php

namespace App\images;

use App\http;
use App\utils;

/**
 * Store the images (covers and avatars) on the disk.
 *
 * The images are resized (400x400px by default) and saved as Webp. They are
 * named after a hash (of their URL if fetched, or of their content if
 * uploaded) so a file never changes once it is saved and can be shared
 * between records.
 *
 * @phpstan-type MediaType 'covers'|'avatars'
 * @phpstan-type ImageSize array{positive-int, positive-int}
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Storage
{
    /** @var ImageSize */
    public const DEFAULT_SIZE = [400, 400];

    private http\Fetcher $fetcher;

    public function __construct()
    {
        $this->fetcher = new http\Fetcher(
            http_timeout: 10,
            http_max_size: 5 * 1024 * 1024,
            default_cache_duration: 1 * 60 * 60 * 24 * 7,
            min_cache_duration: 1 * 60 * 60 * 24 * 7,
            max_cache_duration: 1 * 60 * 60 * 24 * 30,
            ignore_rate_limit: true,
        );
    }

    /**
     * Return the path of a media file on the disk.
     *
     * @param MediaType $type
     */
    public static function filepath(string $type, string $filename): string
    {
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($filename);
        return "{$media_path}/{$type}/{$subpath}/{$filename}";
    }

    /**
     * Fetch and save an image, and return its filename on the disk.
     *
     * @param MediaType $type
     * @param ImageSize $size
     *
     * @throws StorageError if the image cannot be fetched or read
     */
    public function storeFromUrl(string $image_url, string $type, array $size = self::DEFAULT_SIZE): string
    {
        if (!$image_url) {
            throw new StorageError('URL cannot be empty');
        }

        $hash = self::hash($image_url, $size);

        $existing_filename = $this->findFilename($type, $hash);
        if ($existing_filename) {
            return $existing_filename;
        }

        try {
            $response = $this->fetcher->get($image_url, type: 'image');
        } catch (http\FetcherError $e) {
            throw new StorageError("Cannot fetch the image {$image_url}", previous: $e);
        }

        if (!$response->success) {
            throw new StorageError("Cannot fetch the image {$image_url} (HTTP {$response->status})");
        }

        return $this->save($type, $hash, $response->data, $size);
    }

    /**
     * Save an image file and return its filename on the disk.
     *
     * @param MediaType $type
     * @param ImageSize $size
     *
     * @throws StorageError if the file cannot be read as an image
     */
    public function storeFromFile(\Minz\File $file, string $type, array $size = self::DEFAULT_SIZE): string
    {
        $data = $file->content();

        if ($data === false) {
            throw new StorageError('Cannot read the file');
        }

        $hash = self::hash($data, $size);

        $existing_filename = $this->findFilename($type, $hash);
        if ($existing_filename) {
            return $existing_filename;
        }

        return $this->save($type, $hash, $data, $size);
    }

    /**
     * Delete an image from the disk.
     *
     * @param MediaType $type
     */
    public function remove(string $type, string $filename): void
    {
        @unlink(self::filepath($type, $filename));
    }

    /**
     * Return the hash used to name the image generated from the given source
     * (URL or content) and size.
     *
     * The size is part of the hash only if it differs from the default size.
     *
     * @param ImageSize $size
     */
    private static function hash(string $source, array $size): string
    {
        if ($size !== self::DEFAULT_SIZE) {
            [$width, $height] = $size;
            $source = "{$source}#{$width}x{$height}";
        }

        return hash('sha256', $source);
    }

    /**
     * Return the filename of an image already saved under the given hash, or
     * null if there is none.
     *
     * @param MediaType $type
     */
    private function findFilename(string $type, string $hash): ?string
    {
        $filepaths = glob(self::filepath($type, $hash) . '.*');

        if (!$filepaths) {
            return null;
        }

        return basename($filepaths[0]);
    }

    /**
     * Resize the image and save it under the given hash. Return the filename.
     *
     * @param MediaType $type
     * @param ImageSize $size
     *
     * @throws StorageError if the data cannot be read as an image
     * @throws \RuntimeException if the image cannot be saved
     */
    private function save(string $type, string $hash, string $data, array $size): string
    {
        [$width, $height] = $size;

        try {
            $image = Image::fromString($data);
        } catch (\DomainException $e) {
            throw new StorageError('Cannot read the data as an image', previous: $e);
        }

        $image->resize($width, $height);

        $filename = "{$hash}.webp";
        $filepath = self::filepath($type, $filename);
        $directory = dirname($filepath);

        if (!file_exists($directory)) {
            @mkdir($directory, 0755, true);
        }

        // The image is saved in a temporary file, then renamed, so the final
        // file is never partially written. The temporary file is hidden so it
        // is ignored by the `glob()` calls.
        $tmp_filepath = "{$directory}/." . \Minz\Random::hex(16) . '.tmp';

        if (!$image->save($tmp_filepath) || !rename($tmp_filepath, $filepath)) {
            @unlink($tmp_filepath);
            throw new \RuntimeException("Cannot save the image {$filepath}");
        }

        return $filename;
    }
}
