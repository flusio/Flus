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

    private string $path_cards;

    private string $path_covers;

    private string $path_large;

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
        $this->path_cards = "{$media_path}/cards";
        $this->path_covers = "{$media_path}/covers";
        $this->path_large = "{$media_path}/large";
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
        $path_card = "{$this->path_cards}/{$subpath}";
        $path_cover = "{$this->path_covers}/{$subpath}";
        $path_large = "{$this->path_large}/{$subpath}";
        $card_image_filepath = "{$path_card}/{$url_hash}";
        $cover_image_filepath = "{$path_cover}/{$url_hash}";
        $large_image_filepath = "{$path_large}/{$url_hash}";

        if (!file_exists($path_card)) {
            @mkdir($path_card, 0755, true);
        }
        if (!file_exists($path_cover)) {
            @mkdir($path_cover, 0755, true);
        }
        if (!file_exists($path_large)) {
            @mkdir($path_large, 0755, true);
        }

        $card_file_exists = glob($card_image_filepath . '.*');
        $cover_file_exists = glob($cover_image_filepath . '.*');
        $large_file_exists = glob($large_image_filepath . '.*');

        if ($card_file_exists && $cover_file_exists && $large_file_exists) {
            return basename($card_file_exists[0]);
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
            // We generate three different images from the source to keep the
            // quality as high as possible
            $card_image = models\Image::fromString($response->data);
            $card_image->resize(300, 150);
            $card_image->save($card_image_filepath . '.webp');

            $cover_image = models\Image::fromString($response->data);
            $cover_image->resize(300, 300);
            $cover_image->save($cover_image_filepath . '.webp');

            $large_image = models\Image::fromString($response->data);
            $large_image->resize(1100, 250);
            $large_image->save($large_image_filepath . '.webp');

            return $url_hash . '.webp';
        } catch (\DomainException $e) {
            \Minz\Log::warning("Can’t save preview image ({$image_url})");
            return '';
        }
    }
}
