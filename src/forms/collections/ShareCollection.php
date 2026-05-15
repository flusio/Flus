<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Request;
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
    use utils\Memoizer;

    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The type is required.'),
    )]
    #[Validable\Inclusion(
        in: models\CollectionShare::VALID_TYPES,
        message: new Translatable('The type is invalid.'),
    )]
    public string $type = 'read';

    #[Form\Field(transform: 'trim')]
    public string $user_id = '';

    public function user(): models\User
    {
        return $this->memoize('user', function (): models\User {
            return models\User::require($this->user_id);
        });
    }

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

    #[Form\OnHandleRequest]
    public function extractUserIdFromProfileUrl(Request $request): void
    {
        list($origin_type, $origin_id) = utils\OriginHelper::extractFromPath($this->user_id);

        if ($origin_type === 'user' && $origin_id) {
            $this->user_id = $origin_id;
        }
    }

    #[Validable\Check]
    public function checkUserIdIsValid(): void
    {
        $collection = $this->optionAs('collection', models\Collection::class);
        $support_user = models\User::supportUser();

        if (
            !models\User::exists($this->user_id) ||
            $support_user->id === $this->user_id
        ) {
            $this->addError(
                'user_id',
                'user_id.unknown',
                _('This user doesn’t exist.'),
            );
            return;
        }


        if ($collection->user_id === $this->user_id) {
            $this->addError(
                'user_id',
                'user_id.same_as_owner',
                _('You can’t share access with the owner of the collection.'),
            );
            return;
        }

        $user = $this->user();

        if ($collection->sharedWith($user)) {
            $this->addError(
                'user_id',
                'user_id.already_shared',
                _('The collection is already shared with this user.'),
            );
            return;
        }
    }
}
