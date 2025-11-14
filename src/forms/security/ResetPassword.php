<?php

namespace App\forms\security;

use App\forms\BaseForm;
use App\models;
use Minz\Form;
use Minz\Validable;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ResetPassword extends BaseForm
{
    #[Form\Field(bind: 'setPassword')]
    public string $password = '';

    #[Form\Field(bind: false)]
    public string $t = '';

    #[Validable\Check]
    public function checkTokenIsValid(): void
    {
        $token = models\Token::require($this->t);
        if (!$token->isValid()) {
            $this->addError(
                '@base',
                'invalid_token',
                _('The token has expired, you should reset your password again.'),
            );
        }
    }
}
