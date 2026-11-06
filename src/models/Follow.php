<?php

namespace App\models;

use App\models\database\BulkQueries;
use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'followed_collections')]
class Follow
{
    use BulkQueries;
    use Database\Recordable;
    use media\HasImage;
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
    #[Validable\Presence(
        message: new Translatable('The filter is required.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_TIME_FILTERS,
        message: new Translatable('The filter is invalid.'),
    )]
    public string $time_filter;

    #[Database\Column]
    #[Validable\Length(
        max: Source::NAME_MAX_LENGTH,
        message: new Translatable('The name must be less than {max} characters.'),
    )]
    public string $name = '';

    public function __construct(User $user, Source $source)
    {
        $this->time_filter = 'normal';
        $this->user_id = $user->id;
        $this->collection_id = $source->id;
    }

    public static function findOrCreate(User $user, Source $source): self
    {
        return self::findOrCreateBy([
            'user_id' => $user->id,
            'collection_id' => $source->id,
        ], [
            'time_filter' => 'normal',
        ]);
    }

    /**
     * Return the follows of the given user for the given sources, indexed
     * by the ids of these sources.
     *
     * The sources that the user doesn't follow are absent from the returned array.
     *
     * @param Source[] $sources
     *
     * @return array<string, self>
     */
    public static function listByUserAndSources(User $user, array $sources): array
    {
        if (!$sources) {
            return [];
        }

        $follows = self::listBy([
            'user_id' => $user->id,
            'collection_id' => array_column($sources, 'id'),
        ]);

        $follows_by_collection_ids = [];

        foreach ($follows as $follow) {
            $follows_by_collection_ids[$follow->collection_id] = $follow;
        }

        return $follows_by_collection_ids;
    }

    /**
     * Update the time filter of the follows of the given user for the given sources.
     *
     * @param Source[] $sources
     */
    public static function updateTimeFilter(User $user, array $sources, string $time_filter): void
    {
        if (!$sources) {
            return;
        }

        $source_ids = array_column($sources, 'id');

        $ids_as_question_marks = array_fill(0, count($source_ids), '?');
        $ids_as_question_marks = implode(', ', $ids_as_question_marks);

        $sql = <<<SQL
            UPDATE followed_collections
            SET time_filter = ?
            WHERE user_id = ?
            AND collection_id IN ({$ids_as_question_marks})
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$time_filter, $user->id, ...$source_ids]);
    }

    /**
     * Return the streams in which the followed source is displayed.
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
     * Set the streams in which the followed source is displayed.
     *
     * The streams that are not in the given list are detached.
     *
     * @param Stream[] $streams
     */
    public function setStreams(array $streams): void
    {
        StreamToFollow::set($this, $streams, Stream::listByFollow($this));

        // Make sure to deduplicate streams if they are given several times.
        $streams = array_values(array_column($streams, null, 'id'));
        $streams = utils\Sorter::localeSort($streams, 'name');
        $this->memoizeValue('streams', $streams);
    }
}
