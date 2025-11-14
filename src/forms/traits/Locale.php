<?php

namespace App\forms\traits;

use App\models;
use App\utils;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Locale
{
    #[Form\Field(transform: 'trim')]
    #[Validable\Presence(
        message: new Translatable('The locale is required.'),
    )]
    #[models\checks\Locale(
        message: new Translatable('The locale is invalid.'),
    )]
    public string $locale = '';

    /**
     * @return array<string, string>
     */
    public function localesValues(): array
    {
        return utils\Locale::availableLocales();
    }
}
