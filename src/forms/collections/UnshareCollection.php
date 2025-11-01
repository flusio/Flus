<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use Minz\Form;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UnshareCollection extends BaseForm
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
