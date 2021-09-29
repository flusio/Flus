<?php

namespace flusio\services;

use flusio\utils;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Image
{
    /** @var \SpiderBits\Http */
    private $http;

    /** @var string */
    private $path_cards;

    /** @var string */
    private $path_covers;

    /** @var string */
    private $path_large;

    public function __construct()
    {
        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->timeout = 10;

        $media_path = \Minz\Configuration::$application['media_path'];
        $this->path_cards = "{$media_path}/cards";
        $this->path_covers = "{$media_path}/covers";
        $this->path_large = "{$media_path}/large";
    }

    /**
     * Generate preview images
     *
     * @param string $image_url
     *
     * @return string
     *     The generated image filename on the disk (or an empty string on failure)
     */
    public function generatePreviews($image_url)
    {
        $url_hash = \SpiderBits\Cache::hash($image_url);
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

        models\FetchLog::log($image_url, 'image');
        try {
            $options = [
                'max_size' => 20 * 1024 * 1024,
            ];
            $response = $this->http->get($image_url, [], $options);
        } catch (\SpiderBits\HttpError $e) {
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
            $card_image->save($card_image_filepath . '.' . $card_image->type());

            $cover_image = models\Image::fromString($response->data);
            $cover_image->resize(300, 300);
            $cover_image->save($cover_image_filepath . '.' . $card_image->type());

            $large_image = models\Image::fromString($response->data);
            $large_image->resize(1100, 250);
            $large_image->save($large_image_filepath . '.' . $large_image->type());

            return $url_hash . '.' . $card_image->type();
        } catch (\DomainException $e) {
            \Minz\Log::warning("Can’t save preview image ({$image_url})");
            return '';
        }
    }
}
