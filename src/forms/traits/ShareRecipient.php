<?php

namespace App\forms\traits;

use App\models;
use App\utils;
use Minz\Form;
use Minz\Request;
use Minz\Validable;

/**
 * Handle the designation of the user with whom a resource is shared.
 *
 * The recipient is declared by the URL of its profile (or by its raw id) in
 * the user_id field.
 *
 * The forms using this trait must declare how the shared resource relates to
 * the recipient by implementing the four abstract methods.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait ShareRecipient
{
    use utils\Memoizer;

    #[Form\Field(transform: 'trim')]
    public string $user_id = '';

    public function user(): models\User
    {
        return $this->memoize('user', function (): models\User {
            return models\User::require($this->user_id);
        });
    }

    #[Form\OnHandleRequest]
    public function extractUserIdFromProfileUrl(Request $request): void
    {
        list($origin_type, $origin_id) = utils\OriginHelper::extractFromPath($this->user_id);

        if ($origin_type === 'user' && $origin_id) {
            $this->user_id = $origin_id;
        }
    }

    #[Validable\Check]
    public function checkRecipientIsValid(): void
    {
        if (!models\User::exists($this->user_id)) {
            $this->addError(
                'user_id',
                'user_id.unknown',
                _('This user doesn’t exist.'),
            );
            return;
        }

        $user = $this->user();

        if ($this->isRecipientOwner($user)) {
            $this->addError(
                'user_id',
                'user_id.same_as_owner',
                $this->recipientIsOwnerError(),
            );
            return;
        }

        if ($this->isAlreadySharedWith($user)) {
            $this->addError(
                'user_id',
                'user_id.already_shared',
                $this->alreadySharedError(),
            );
            return;
        }
    }

    /**
     * Return whether the given user owns the shared resource.
     */
    abstract protected function isRecipientOwner(models\User $user): bool;

    /**
     * Return whether the resource is already shared with the given user.
     */
    abstract protected function isAlreadySharedWith(models\User $user): bool;

    /**
     * Return the error message when sharing with the owner of the resource.
     */
    abstract protected function recipientIsOwnerError(): string;

    /**
     * Return the error message when the resource is already shared with the
     * recipient.
     */
    abstract protected function alreadySharedError(): string;
}
