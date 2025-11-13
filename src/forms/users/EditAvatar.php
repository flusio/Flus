<?php

namespace App\forms\users;

use App\forms\BaseForm;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditAvatar extends BaseForm
{
    public const IMAGE_TYPES = [
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The file is required.'),
    )]
    #[Validable\File(
        types: self::IMAGE_TYPES,
        types_message: new Translatable('The file type must be one of the following: {types}.'),
        max_size_message: new Translatable('This file is too large (max allowed: {max_size}).'),
        message: new Translatable('This file cannot be uploaded (error {code}).'),
    )]
    public ?\Minz\File $avatar = null;

    public function imageAcceptedMimetypes(): string
    {
        $accept = [];
        foreach (self::IMAGE_TYPES as $mimetypes) {
            $accept = array_merge($accept, $mimetypes);
        }
        return implode(', ', $accept);
    }
}
