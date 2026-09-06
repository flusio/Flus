<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\forms\traits;
use App\models;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @phpstan-import-type ShareType from models\CollectionShare
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ShareCollection extends BaseForm
{
    use traits\ShareRecipient;

    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The type is required.'),
    )]
    #[Validable\Inclusion(
        in: models\CollectionShare::VALID_TYPES,
        message: new Translatable('The type is invalid.'),
    )]
    public string $type = 'read';

    /**
     * @return ShareType
     */
    public function type(): string
    {
        if (!in_array($this->type, models\CollectionShare::VALID_TYPES)) {
            throw new \LogicException("Type {$this->type} is invalid.");
        }

        return $this->type;
    }

    protected function isRecipientOwner(models\User $user): bool
    {
        $collection = $this->optionAs('collection', models\Collection::class);
        return $collection->user_id === $user->id;
    }

    protected function isAlreadySharedWith(models\User $user): bool
    {
        $collection = $this->optionAs('collection', models\Collection::class);
        return $collection->sharedWith($user);
    }

    protected function recipientIsOwnerError(): string
    {
        return _('You can’t share access with the owner of the collection.');
    }

    protected function alreadySharedError(): string
    {
        return _('The collection is already shared with this user.');
    }
}
