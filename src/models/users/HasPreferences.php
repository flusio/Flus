<?php

namespace App\models\users;

use App\models\checks;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * Add the preferences that the users can change to customize their experience.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasPreferences
{
    public const VALID_FONT_FAMILIES = ['default', 'system-ui'];
    public const VALID_TEXT_SIZES = ['small', 'medium', 'large'];
    public const VALID_COLOR_SCHEMES = ['system', 'light', 'dark'];

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The locale is required.'),
    )]
    #[checks\Locale(
        message: new Translatable('The locale is invalid.'),
    )]
    public string $locale;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The font family is required. Please select an option from the list.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_FONT_FAMILIES,
        message: new Translatable('The font family is invalid. Please select an option from the list.'),
    )]
    public string $option_font_family = 'default';

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The text size is required. Please select an option from the list.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_TEXT_SIZES,
        message: new Translatable('The text size is invalid. Please select an option from the list.'),
    )]
    public string $option_text_size = 'medium';

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The theme is required. Please select an option from the list.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_COLOR_SCHEMES,
        message: new Translatable('The theme is invalid. Please select an option from the list.'),
    )]
    public string $option_color_scheme = 'system';

    #[Database\Column]
    public bool $option_compact_mode = false;

    #[Database\Column]
    public bool $accept_contact;
}
