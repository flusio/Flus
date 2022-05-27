<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Image
{
    /** @var resource */
    private $resource;

    /** @var integer */
    private $width;

    /** @var integer */
    private $height;

    /** @var string */
    private $type;

    /**
     * @param resource $resource The image as GD resource
     * @param string $type jpeg, png or webp
     */
    public function __construct($resource, $type)
    {
        $this->resource = $resource;
        $this->type = $type;
        $this->width = imagesx($resource);
        $this->height = imagesy($resource);
    }

    /**
     * Initialize an image from a string
     *
     * @param string $string_image
     *
     * @return \flusio\models\Image
     */
    public static function fromString($string_image)
    {
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $string_image);
        switch (strtolower($mime)) {
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpeg':
                $type = 'jpeg';
                break;
            case 'image/png':
            case 'image/x-png':
                $type = 'png';
                break;
            case 'image/webp':
            case 'image/x-webp':
                $type = 'webp';
                break;
            default:
                throw new \DomainException("The given string doesn’t look like a supported image ({$mime})");
        }

        $resource = @imagecreatefromstring($string_image);
        if (!$resource) {
            throw new \DomainException('Cannot create an image from the given string');
        }

        return new self($resource, $type);
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Resize the current image to the given size.
     *
     * The image is cropped in the middle to keep the proportion of the image.
     *
     * @param integer $width
     * @param integer $height
     */
    public function resize($width, $height)
    {
        $new_resource = imagecreatetruecolor($width, $height);

        // Preserve transparency
        // Code from the Intervention Image library
        // @see https://github.com/Intervention/image/blob/8ee5f346ce8c6dcbdc7dec443486bd5f6ad924ff/src/Intervention/Image/Gd/Commands/ResizeCommand.php
        $transparent_index = imagecolortransparent($this->resource);
        if ($transparent_index !== -1) {
            $rgba = imagecolorsforindex($new_resource, $transparent_index);
            $transparent_color = imagecolorallocatealpha(
                $new_resource,
                $rgba['red'],
                $rgba['green'],
                $rgba['blue'],
                127
            );
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

        imagedestroy($this->resource);
        $this->resource = $new_resource;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Save the image on disk.
     *
     * @param string $filepath
     */
    public function save($filepath)
    {
        switch ($this->type) {
            case 'jpeg':
                imagejpeg($this->resource, $filepath);
                break;
            case 'png':
                imagepng($this->resource, $filepath);
                break;
            case 'webp':
                imagewebp($this->resource, $filepath);
                break;
        }
    }

    /**
     * Get the src rectangle to be used to resize the initial image
     *
     * This function return the bigger rectangle with destination ratio that
     * fits in the initial rectangle.
     *
     * @see https://www.php.net/manual/function.imagecopyresampled
     *
     * @param array $initial_size
     *     The size of the initial image (first value is width, second is height)
     * @param array $destination_size
     *     The size of the desired image (first value is width, second is height)
     *
     * @return array
     *     The src rectange to use in imagecopyresampled, array indexes are:
     *     x, y, width and height.
     */
    public static function resizeRectangle($initial_size, $destination_size)
    {
        list($initial_width, $initial_height) = $initial_size;
        list($destination_width, $destination_height) = $destination_size;

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
