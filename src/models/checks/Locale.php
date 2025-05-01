<?php

namespace App\models\checks;

use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Locale extends \Minz\Validable\PropertyCheck
{
    public function assert(): bool
    {
        $value = $this->value();

        if ($value === null || $value === '') {
            return true;
        }

        $available_locales = utils\Locale::availableLocales();

        return isset($available_locales[$value]);
    }
}
