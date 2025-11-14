<?php

namespace App\forms\security;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AskResetPassword extends BaseForm
{
    use utils\Memoizer;

    #[Form\Field(transform: '\Minz\Email::sanitize')]
    #[Validable\Presence(
        message: new Translatable('The address email is required.'),
    )]
    #[Validable\Email(
        message: new Translatable('The address email is invalid.'),
    )]
    public string $email = '';

    public function user(): models\User
    {
        return $this->memoize('user', function (): models\User {
            return models\User::requireBy([
                'email' => $this->email,
            ]);
        });
    }

    #[Validable\Check]
    public function checkUserExists(): void
    {
        try {
            $this->user();
        } catch (\Minz\Errors\MissingRecordError $error) {
            $this->addError(
                'email',
                'missing_user',
                _('We can’t find any account with this email address.'),
            );
        }
    }
}
