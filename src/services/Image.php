<?php

namespace App\services;

use App\http;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Image
{
    private http\Fetcher $fetcher;

    private string $path_covers;

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

        $media_path = \App\Configuration::$application['media_path'];
        $this->path_covers = "{$media_path}/covers";
    }

    /**
     * Generate preview images and return the generated image filename on the
     * disk (or an empty string on failure).
     *
     * @throws \DomainException if $image_url is empty
     */
    public function generatePreviews(string $image_url): string
    {
        if (empty($image_url)) {
            throw new \DomainException('URL cannot be empty');
        }

        $url_hash = hash('sha256', $image_url);
        $subpath = utils\Belt::filenameToSubpath($url_hash);
        $path_covers = "{$this->path_covers}/{$subpath}";
        $cover_image_filepath = "{$path_covers}/{$url_hash}";

        if (!file_exists($path_covers)) {
            @mkdir($path_covers, 0755, true);
        }

        $cover_file_exists = glob($cover_image_filepath . '.*');

        if ($cover_file_exists) {
            return basename($cover_file_exists[0]);
        }

        try {
            $response = $this->fetcher->get($image_url, type: 'image');
        } catch (http\FetcherError $e) {
            return '';
        }

        if (!$response->success) {
            return '';
        }

        try {
            $cover_image = models\Image::fromString($response->data);
            $cover_image->resize(300, 300);
            $cover_image->save($cover_image_filepath . '.webp');

            return $url_hash . '.webp';
        } catch (\DomainException $e) {
            \Minz\Log::warning("Can’t save preview image ({$image_url})");
            return '';
        }
    }
}
