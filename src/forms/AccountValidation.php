<?php

namespace App\forms;

use App\models;
use Minz\Form;

/**
 * @extends BaseForm<\stdClass>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AccountValidation extends BaseForm
{
    #[Form\Field]
    public string $t = '';

    #[Form\Check]
    public function checkToken(): void
    {
        try {
            $token = $this->getToken();
            $user = $this->getUser();
        } catch (\RuntimeException $e) {
            $this->addError(
                '@global',
                _('The token doesn’t exist. The validation link you clicked on should have expired.')
            );
            return;
        }

        if (!$token->isValid()) {
            $this->addError('@global', _('The token has expired or has been invalidated.'));
            return;
        }
    }

    public function getToken(): models\Token
    {
        $token = models\Token::find($this->t);

        if (!$token) {
            throw new \RuntimeException("Unknown token {$this->t}");
        }

        return $token;
    }

    public function getUser(): models\User
    {
        $user = models\User::findBy(['validation_token' => $this->t]);

        if (!$user) {
            throw new \RuntimeException("Unknown user for token {$this->t}");
        }

        return $user;
    }
}
