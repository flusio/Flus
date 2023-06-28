<?php

namespace flusio\models;

use flusio\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'followed_collections')]
class FollowedCollection
{
    use dao\BulkQueries;
    use Database\Recordable;
    use Validable;

    public const VALID_TIME_FILTERS = ['strict', 'normal', 'all'];

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $collection_id;

    #[Database\Column]
    public ?string $group_id;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The filter is required.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_TIME_FILTERS,
        message: new Translatable('The filter is invalid.'),
    )]
    public string $time_filter;

    public function __construct(string $user_id, string $collection_id)
    {
        $this->time_filter = 'normal';
        $this->user_id = $user_id;
        $this->collection_id = $collection_id;
    }
}
