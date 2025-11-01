<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @phpstan-type ShareType value-of<self::VALID_TYPES>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'collection_shares')]
class CollectionShare
{
    use dao\CollectionShare;
    use Database\Recordable;
    use Validable;

    public const VALID_TYPES = ['read', 'write'];

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $collection_id;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The type is required.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_TYPES,
        message: new Translatable('The type is invalid.'),
    )]
    public string $type;

    // used to sort collection shares easily
    #[Database\Column(computed: true)]
    public ?string $username;

    public function __construct(string $user_id, string $collection_id, string $type)
    {
        $this->user_id = $user_id;
        $this->collection_id = $collection_id;
        $this->type = $type;
    }

    /**
     * Return the user attached to the CollectionShare
     */
    public function user(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("CollectionShare #{$this->id} has invalid user.");
        }

        return $user;
    }
}
