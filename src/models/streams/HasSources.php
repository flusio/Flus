<?php

namespace App\models\streams;

use App\models\Follow;
use App\models\Source;
use App\models\StreamToFollow;
use App\models\User;
use App\utils;
use Minz\Database;

/**
 * Add the relation between a stream and the sources it displays.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasSources
{
    /**
     * Return the sources of the stream.
     *
     * Only the sources that the context user can view are returned, or only
     * the public ones if no context user is given.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     *
     * @return Source[]
     */
    public function sources(array $options = []): array
    {
        $context_user = $options['context_user'] ?? null;
        $memoize_key = $this->memoizeKeyFor('sources', $context_user);

        return $this->memoize($memoize_key, function () use ($context_user): array {
            $sources = Source::listByStream($this, [
                'context_user' => $context_user,
            ]);

            return utils\Sorter::localeSort(
                $sources,
                function (Source $source) use ($context_user): string {
                    return $source->nameByUser($context_user);
                }
            );
        });
    }

    /**
     * Return whether the given source is displayed in the stream.
     */
    public function hasSource(Source $source): bool
    {
        return StreamToFollow::find($this, $source) !== null;
    }

    /**
     * Display the given source in the stream.
     */
    public function addSource(Source $source): void
    {
        StreamToFollow::findOrCreate($this, $source);
        $this->unmemoizePrefixed('sources_');
    }

    /**
     * Remove the given source from the stream.
     */
    public function removeSource(Source $source): void
    {
        $stream_to_follow = StreamToFollow::find($this, $source);
        if ($stream_to_follow) {
            $stream_to_follow->remove();
        }
        $this->unmemoizePrefixed('sources_');
    }

    /**
     * Add the given sources to the stream.
     *
     * @param Source[] $sources
     */
    public function addSources(array $sources): void
    {
        $database = Database::get();
        $database->beginTransaction();

        foreach ($sources as $source) {
            $this->addSource($source);
        }

        $database->commit();
    }

    /**
     * Remove the given sources from the stream.
     *
     * @param Source[] $sources
     */
    public function removeSources(array $sources): void
    {
        $database = Database::get();
        $database->beginTransaction();

        foreach ($sources as $source) {
            $this->removeSource($source);
        }

        $database->commit();
    }

    /**
     * Return the sum of the publication frequencies of the sources.
     *
     * Only the sources that the context user can view are taken into account,
     * or only the public ones if no context user is given.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     */
    public function publicationFrequencyPerYear(array $options = []): int
    {
        $sources = $this->sources($options);
        return array_sum(array_column($sources, 'publication_frequency_per_year'));
    }

    /**
     * List the streams in which the followed source is displayed.
     *
     * @return self[]
     */
    public static function listByFollow(Follow $follow): array
    {
        $sql = <<<SQL
            SELECT s.*
            FROM streams s, streams_to_follows sf

            WHERE s.id = sf.stream_id
            AND sf.follow_id = :follow_id
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            'follow_id' => $follow->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
