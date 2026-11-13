<?php

namespace App\images;

/**
 * Create and manipulate Webp images
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Image
{
    /**
     * The maximum number of pixels of an image that can be loaded, whatever
     * the memory limit is.
     *
     * @see self::maxPixels
     */
    public const MAX_PIXELS = 25_000_000;

    /**
     * The approximate number of bytes per pixel that GD needs to load an image.
     *
     * A loaded image takes about 4 bytes per pixel, but decoding a PNG with
     * transparency can need up to 8 bytes: we consider the worst case.
     */
    private const BYTES_PER_PIXEL = 8;

    private \GdImage $resource;

    private int $width;

    private int $height;

    public function __construct(\GdImage $resource)
    {
        $this->resource = $resource;
        $this->width = imagesx($resource);
        $this->height = imagesy($resource);
    }

    /**
     * Return the maximum number of pixels available to load an image.
     *
     * It is deduced from the memory limit of PHP (without exceeding MAX_PIXELS),
     * so that loading an image never needs more than three quarters of it. It
     * should be enough to prevent images from exhausting the memory.
     */
    public static function maxPixels(): int
    {
        $memory_limit_bytes = ini_parse_quantity(ini_get('memory_limit'));

        if ($memory_limit_bytes <= 0) {
            // The memory is not limited.
            return self::MAX_PIXELS;
        }

        $usable_memory_bytes = $memory_limit_bytes * 0.75;
        $max_pixels = intval($usable_memory_bytes / self::BYTES_PER_PIXEL);

        return min($max_pixels, self::MAX_PIXELS);
    }

    /**
     * Initialize an image from a string
     *
     * @throws \DomainException
     *     If the string cannot be read as an image, or if the image has more
     *     pixels than what maxPixels() allows.
     */
    public static function fromString(string $string_image): self
    {
        $size = @getimagesizefromstring($string_image);

        if (!$size) {
            throw new \DomainException('Cannot read the size of the image');
        }

        [$width, $height] = $size;

        if ($width * $height > self::maxPixels()) {
            throw new \DomainException("The image is too large ({$width}x{$height}px)");
        }

        $resource = @imagecreatefromstring($string_image);

        if (!$resource) {
            throw new \DomainException('Cannot create an image from the given string');
        }

        return new self($resource);
    }

    /**
     * Resize the current image to the given size.
     *
     * The image is cropped in the middle to keep the proportion of the image.
     *
     * @param positive-int $width
     * @param positive-int $height
     */
    public function resize(int $width, int $height): void
    {
        $new_resource = imagecreatetruecolor($width, $height);

        if ($new_resource === false) {
            throw new \Exception('Cannot create a new true color image');
        }

        // Preserve transparency
        // Code from the Intervention Image library
        // @see https://github.com/Intervention/image/blob/8ee5f346ce8c6dcbdc7dec443486bd5f6ad924ff/src/Intervention/Image/Gd/Commands/ResizeCommand.php
        $transparent_index = imagecolortransparent($this->resource);
        if ($transparent_index >= 0) {
            $rgba = imagecolorsforindex($new_resource, $transparent_index);

            $transparent_color = imagecolorallocatealpha(
                $new_resource,
                $rgba['red'],
                $rgba['green'],
                $rgba['blue'],
                127
            );

            if ($transparent_color === false) {
                throw new \Exception('Cannot create a transparent color');
            }

            imagefill($new_resource, 0, 0, $transparent_color);
            imagecolortransparent($new_resource, $transparent_color);
        } else {
            imagealphablending($new_resource, false);
            imagesavealpha($new_resource, true);
        }

        $src_rect = self::resizeRectangle(
            [$this->width, $this->height],
            [$width, $height]
        );

        imagecopyresampled(
            $new_resource,
            $this->resource,
            0,
            0,
            $src_rect['x'],
            $src_rect['y'],
            $width,
            $height,
            $src_rect['width'],
            $src_rect['height']
        );

        $this->resource = $new_resource;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Save the image on disk.
     */
    public function save(string $filepath): bool
    {
        return imagewebp($this->resource, $filepath);
    }

    /**
     * Get the src rectangle to be used to resize the initial image
     *
     * This function return the bigger rectangle with destination ratio that
     * fits in the initial rectangle.
     *
     * @see https://www.php.net/manual/function.imagecopyresampled
     *
     * @param array{int, int} $initial_size
     *     The size of the initial image (first value is width, second is height)
     * @param array{int, int} $destination_size
     *     The size of the desired image (first value is width, second is height)
     *
     * @return array{
     *     'x': int,
     *     'y': int,
     *     'width': int,
     *     'height': int,
     * } The src rectange to use in imagecopyresampled.
     */
    public static function resizeRectangle(array $initial_size, array $destination_size): array
    {
        [$initial_width, $initial_height] = $initial_size;
        [$destination_width, $destination_height] = $destination_size;

        $initial_ratio = $initial_width / $initial_height;
        $destination_ratio = $destination_width / $destination_height;
        if ($initial_ratio <= $destination_ratio) {
            // The current width will entirely fit in the future image, so we
            // can take the full width. We have to crop the height though
            // (taking the middle of the image).
            $src_width = $initial_width;
            $src_height = $initial_width / $destination_ratio;
            $src_x = 0;
            $src_y = ($initial_height - $src_height) / 2;
        } else {
            // Same thing, but with height which entirely fit in the future
            // image.
            $src_width = $initial_height * $destination_ratio;
            $src_height = $initial_height;
            $src_x = ($initial_width - $src_width) / 2;
            $src_y = 0;
        }

        return [
            'x' => (int)$src_x,
            'y' => (int)$src_y,
            'width' => (int)$src_width,
            'height' => (int)$src_height,
        ];
    }
}
