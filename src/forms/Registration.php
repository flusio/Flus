<?php

namespace App\forms;

use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Registration extends BaseForm
{
    use utils\Memoizer;

    #[Form\Field(bind: 'setUsername')]
    public string $username = '';

    #[Form\Field(bind: 'setEmail')]
    public string $email = '';

    #[Form\Field(bind: 'setPassword')]
    public string $password = '';

    #[Form\Field]
    public bool $accept_contact = false;

    #[Form\Field(bind: false)]
    public bool $accept_terms = false;

    public int $username_max_length = models\User::USERNAME_MAX_LENGTH;

    public function hasTerms(): bool
    {
        return $this->memoize('has_terms', function (): bool {
            $terms_path = \App\Configuration::$app_path . '/policies/terms.html';
            return file_exists($terms_path);
        });
    }

    #[Validable\Check]
    public function checkTermsAreAccepted(): void
    {
        if ($this->hasTerms() && !$this->accept_terms) {
            $this->addError(
                'accept_terms',
                'terms_not_accepted',
                _('You must accept the terms of service.'),
            );
        }
    }
}
