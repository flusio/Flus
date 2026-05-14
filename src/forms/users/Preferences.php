<?php

namespace App\forms\users;

use App\forms\BaseForm;
use App\forms\traits;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Preferences extends BaseForm
{
    use traits\Locale;

    #[Form\Field]
    public string $option_font_family = 'default';

    #[Form\Field]
    public string $option_text_size = 'medium';

    #[Form\Field]
    public string $option_color_scheme = 'system';

    #[Form\Field]
    public bool $option_compact_mode = false;

    #[Form\Field]
    public bool $accept_contact = false;

    #[Form\Field(bind: false)]
    public bool $beta_enabled = false;

    /**
     * @param array<string, mixed> $default_values
     */
    public function __construct(array $default_values = [], ?models\User $model = null)
    {
        if ($model) {
            $default_values['beta_enabled'] = $model->isBetaEnabled();
        }

        parent::__construct($default_values, $model);
    }

    /**
     * @return array<string, string>
     */
    public function fontFamilyValues(): array
    {
        return [
            'default' => \Minz\Template\TwigExtension::translate('Raleway (default)'),
            'system-ui' => \Minz\Template\TwigExtension::translate('System font'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function textSizeValues(): array
    {
        return [
            'small' => \Minz\Template\TwigExtension::translate('Small'),
            'medium' => \Minz\Template\TwigExtension::translate('Medium'),
            'large' => \Minz\Template\TwigExtension::translate('Large'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function colorSchemeValues(): array
    {
        return [
            'system' => \Minz\Template\TwigExtension::translate('System'),
            'light' => \Minz\Template\TwigExtension::translate('Light'),
            'dark' => \Minz\Template\TwigExtension::translate('Dark'),
        ];
    }
}
