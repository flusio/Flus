<?php

namespace App\models\collections;

use App\utils\Belt;
use Minz\Database;

/**
 * Add the information and the queries related to the retrieval of a feed.
 *
 * @phpstan-import-type Serie from \App\jobs\traits\JobInSerie
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Fetchable
{
    #[Database\Column]
    public ?string $feed_url = null;

    #[Database\Column]
    public ?string $feed_type = null;

    #[Database\Column]
    public ?string $feed_site_url = null;

    #[Database\Column]
    public ?string $feed_last_hash = null;

    #[Database\Column]
    public int $feed_fetched_code = 0;

    #[Database\Column]
    public ?\DateTimeImmutable $feed_fetched_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $feed_fetched_next_at = null;

    #[Database\Column]
    public ?string $feed_fetched_error = null;

    public static function initFeed(string $feed_url): self
    {
        $collection = new self();

        $collection->name = Belt::cut($feed_url, self::NAME_MAX_LENGTH);
        $collection->feed_url = \SpiderBits\Url::sanitize($feed_url);
        $collection->type = 'feed';
        $collection->is_public = true;

        return $collection;
    }

    public static function findOrBuildFeed(string $feed_url): self
    {
        $feed_url = \SpiderBits\Url::sanitize($feed_url);

        $feed = self::findBy([
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);

        if (!$feed) {
            $feed = self::initFeed($feed_url);
        }

        return $feed;
    }

    /**
     * Return the feed's site URL if any, or feed URL.
     */
    public function feedWebsiteUrl(): string
    {
        if ($this->feed_site_url) {
            return $this->feed_site_url;
        } elseif ($this->feed_url) {
            return $this->feed_url;
        } else {
            throw new \LogicException('Cannot call this method on non-feed collection');
        }
    }

    /**
     * Return the feed site URL to be displayed
     */
    public function feedWebsiteHost(): string
    {
        $url = $this->feedWebsiteUrl();
        return Belt::host($url);
    }

    /**
     * List the feeds to be fetched.
     *
     * A "serie" can be passed in order to only return the feeds with an id
     * matching the serie. This allows to fetch the feeds with several jobs in
     * parallel.
     *
     * The list is limited by the $max parameter.
     *
     * @param ?Serie $serie
     *
     * @return self[]
     */
    public static function listFeedsToFetch(int $max = 25, ?array $serie = null): array
    {
        $parameters = [
            ':before' => \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
            ':max' => $max,
        ];

        $clause_serie = '';
        if ($serie && $serie['total'] > 1) {
            $clause_serie = 'AND MOD(c.id::bigint, :total_number_series) = :serie_number';
            $parameters[':total_number_series'] = $serie['total'];
            $parameters[':serie_number'] = $serie['number'];
        }

        $sql = <<<SQL
            SELECT c.*
            FROM collections c

            WHERE c.type = 'feed'
            AND (
                c.feed_fetched_next_at <= :before
                OR c.feed_fetched_next_at IS NULL
            )

            {$clause_serie}

            ORDER BY feed_fetched_next_at NULLS FIRST
            LIMIT :max
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the list of ids indexed by feed urls.
     *
     * @return array<string, string>
     */
    public static function listFeedUrlsToIds(): array
    {
        $sql = <<<SQL
            SELECT feed_url, id FROM collections
            WHERE type = 'feed'
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Return the list of feeds of the given user and with the given feed URLs.
     *
     * @param string[] $feed_urls
     * @param string[] $selected_computed_props
     *
     * @return self[]
     */
    public static function listComputedFeedsByFeedUrls(array $feed_urls, array $selected_computed_props): array
    {
        if (!$feed_urls) {
            return [];
        }

        $urls_as_question_marks = array_fill(0, count($feed_urls), '?');
        $urls_where_statement = implode(', ', $urls_as_question_marks);

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links
            FROM collections c

            LEFT JOIN links_to_collections lc
            ON lc.collection_id = c.id

            WHERE c.type = 'feed'
            AND c.feed_url IN ({$urls_where_statement})
            AND c.is_public = true

            GROUP BY c.id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($feed_urls);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Set all the feed_last_hash to empty string.
     */
    public static function resetHashes(): int
    {
        $sql = <<<SQL
            UPDATE collections
            SET feed_last_hash = ''
            WHERE type = 'feed'
        SQL;

        $database = Database::get();
        return $database->exec($sql);
    }
}
