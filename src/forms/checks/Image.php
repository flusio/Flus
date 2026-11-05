<?php

namespace App\forms\checks;

use Minz\Translatable;
use Minz\Validable;

/**
 * Check that a file is an image of one of the given types.
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
    ) {
        $types_to_mimetypes = [];
        foreach ($types as $type) {
            if (!isset(self::MIMETYPES[$type])) {
                throw new \LogicException("Image type {$type} is not supported.");
            }

            $types_to_mimetypes[$type] = self::MIMETYPES[$type];
        }

        parent::__construct(
            message: (string) $message,
            types_message: (string) $types_message,
            max_size_message: (string) $max_size_message,
            types: $types_to_mimetypes,
        );
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
