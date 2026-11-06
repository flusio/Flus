<?php

namespace App\forms\checks;

use App\images;
use Minz\Translatable;
use Minz\Validable;

/**
 * Check that a file is an image of one of the given types, and that it can be
 * read (i.e. it is not corrupted and its dimensions are not too large).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Image extends Validable\File
{
    public const MIMETYPES = [
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    public string $image_message;

    private bool $is_invalid_image = false;

    /**
     * @param string[] $types
     *     The list of accepted extensions (e.g. ['png', 'jpg']).
     */
    public function __construct(
        array $types,
        string|Translatable $message = new Translatable('This file cannot be uploaded (error {code}).'),
        string|Translatable $types_message = new Translatable(
            'The file type must be one of the following: {types}.'
        ),
        string|Translatable $max_size_message = new Translatable('This file is too large (max allowed: {max_size}).'),
        string|Translatable $image_message = new Translatable(
            'This image cannot be read or its dimensions are too large.'
        ),
    ) {
        $types_to_mimetypes = [];
        foreach ($types as $type) {
            if (!isset(self::MIMETYPES[$type])) {
                throw new \LogicException("Image type {$type} is not supported.");
            }

            $types_to_mimetypes[$type] = self::MIMETYPES[$type];
        }

        $this->image_message = (string) $image_message;

        parent::__construct(
            message: (string) $message,
            types_message: (string) $types_message,
            max_size_message: (string) $max_size_message,
            types: $types_to_mimetypes,
        );
    }

    public function assert(): bool
    {
        $this->is_invalid_image = false;

        if (!parent::assert()) {
            return false;
        }

        $file = $this->value();

        if ($file === null) {
            return true;
        }

        $data = $file->content();

        if ($data === false) {
            $this->is_invalid_image = true;
            return false;
        }

        try {
            images\Image::fromString($data);
        } catch (\DomainException $e) {
            $this->is_invalid_image = true;
            return false;
        }

        return true;
    }

    public function message(): string
    {
        if ($this->is_invalid_image) {
            return $this->image_message;
        }

        return parent::message();
    }

    public function code(): string
    {
        if ($this->is_invalid_image) {
            return parent::code() . '.invalid_image';
        }

        return parent::code();
    }

    /**
     * Return the accepted mimetypes, formatted for the HTML `accept` attribute.
     */
    public function acceptedMimetypes(): string
    {
        $mimetypes = [];
        foreach ($this->types ?? [] as $type_mimetypes) {
            $mimetypes = array_merge($mimetypes, $type_mimetypes);
        }

        return implode(', ', array_unique($mimetypes));
    }
}
