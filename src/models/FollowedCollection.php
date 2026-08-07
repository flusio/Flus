<?php

namespace App\models;

use App\utils;
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
    use utils\Memoizer;

    public const VALID_TIME_FILTERS = ['none', 'strict', 'normal', 'all'];

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

    public static function findOrCreate(User $user, Collection $collection): self
    {
        return self::findOrCreateBy([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ], [
            'time_filter' => 'normal',
        ]);
    }

    /**
     * Return the follows of the given user for the given collections, indexed
     * by the ids of these collections.
     *
     * The collections that the user doesn't follow are absent from the
     * returned array.
     *
     * @param Collection[] $collections
     *
     * @return array<string, self>
     */
    public static function listByUserAndCollections(User $user, array $collections): array
    {
        if (!$collections) {
            return [];
        }

        $follows = self::listBy([
            'user_id' => $user->id,
            'collection_id' => array_column($collections, 'id'),
        ]);

        $follows_by_collection_ids = [];

        foreach ($follows as $follow) {
            $follows_by_collection_ids[$follow->collection_id] = $follow;
        }

        return $follows_by_collection_ids;
    }

    /**
     * Return the streams in which the followed collection is a source.
     *
     * The streams are sorted by name.
     *
     * @return Stream[]
     */
    public function streams(): array
    {
        return $this->memoize('streams', function (): array {
            $streams = Stream::listByFollow($this);
            return utils\Sorter::localeSort($streams, 'name');
        });
    }

    /**
     * Set the streams in which the followed collection is a source.
     *
     * The streams that are not in the given list are detached.
     *
     * @param Stream[] $streams
     */
    public function setStreams(array $streams): void
    {
        StreamToFollow::setStreams($this, $streams);
        $this->unmemoize('streams');
    }
}
