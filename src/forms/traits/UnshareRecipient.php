<?php

namespace App\forms\traits;

use App\models;
use Minz\Form;
use Minz\Validable;

/**
 * Handle the designation of the user with whom a resource is unshared.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait UnshareRecipient
{
    #[Form\Field(transform: 'trim')]
    public string $user_id = '';

    public function user(): models\User
    {
        return models\User::require($this->user_id);
    }

    #[Validable\Check]
    public function checkUserIdIsValid(): void
    {
        if (!models\User::exists($this->user_id)) {
            $this->addError(
                '@base',
                'user_id.unknown',
                _('This user doesn’t exist.'),
            );
            return;
        }
    }
}
