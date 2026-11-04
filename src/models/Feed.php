<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * An external feed (RSS, Atom or JSON Feed).
 *
 * @phpstan-import-type Serie from \App\jobs\traits\JobInSerie
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feed extends Source
{
    use Database\Lockable;

    #[Database\Column]
    public string $feed_url;

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

    #[Database\Column]
    public ?\DateTimeImmutable $image_fetched_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $locked_at = null;

    public static function init(string $feed_url): self
    {
        $feed = new self();

        $feed->name = utils\Belt::cut($feed_url, self::NAME_MAX_LENGTH);
        $feed->feed_url = \SpiderBits\Url::sanitize($feed_url);
        $feed->type = 'feed';
        $feed->is_public = true;

        return $feed;
    }

    public static function findOrBuildByUrl(string $feed_url): self
    {
        $feed_url = \SpiderBits\Url::sanitize($feed_url);

        $feed = self::findBy([
            'feed_url' => $feed_url,
        ]);

        if (!$feed) {
            $feed = self::init($feed_url);
        }

        return $feed;
    }

    /**
     * Return the URL of the original feed.
     */
    public function feedUrl(bool $direct = false): string
    {
        return $this->feed_url;
    }

    /**
     * Return the feed's site URL if any, or feed URL.
     */
    public function feedWebsiteUrl(): string
    {
        return $this->feed_site_url ?: $this->feed_url;
    }

    /**
     * Return the feed site URL to be displayed
     */
    public function feedWebsiteHost(): string
    {
        $url = $this->feedWebsiteUrl();
        return utils\Belt::host($url);
    }

    /**
     * Return the description as sanitized HTML.
     */
    public function descriptionAsHtml(): string
    {
        return utils\HtmlSanitizer::sanitizeCollectionDescription($this->description, $this->feedWebsiteUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toJson(User $context_user): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nameByUser($context_user),
            'description' => $this->description,
            'group' => null,
            'url' => $this->feed_url,
            'type' => $this->feed_type,
            'site_url' => $this->feed_site_url,
            'is_followed' => $context_user->isFollowing($this),
            'publication_frequency_per_year' => $this->publication_frequency_per_year,
        ];
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
    public static function listToFetch(int $max = 25, ?array $serie = null): array
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
     * Return the list of public feeds with the given feed URLs and their
     * number of links.
     *
     * @param string[] $feed_urls
     *
     * @return self[]
     */
    public static function listComputedByFeedUrls(array $feed_urls): array
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
     * Delete the unfollowed feeds older than the given date.
     */
    public static function deleteUnfollowedOlderThan(\DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM collections

            USING collections AS c

            LEFT JOIN followed_collections AS fc
            ON c.id = fc.collection_id

            WHERE collections.id = c.id
            AND c.type = 'feed'
            AND c.created_at < :date
            AND fc.collection_id IS NULL;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);

        return $statement->execute([
            ':date' => $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }

    /**
     * Return the number of feeds indexed by the date - hour of their next
     * retrieval.
     *
     * @return array<string, int>
     */
    public static function countByHours(): array
    {
        $sql = <<<'SQL'
            SELECT TO_CHAR(feed_fetched_next_at, 'YYYY-MM-DD HH24') AS hour, COUNT(*) as count
            FROM collections
            WHERE type = 'feed'
            GROUP BY hour
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        $result = $statement->fetchAll();

        $count_by_hours = [];
        foreach ($result as $row) {
            /** @var string */
            $hour = $row['hour'];
            $count_by_hours[$hour] = intval($row['count']);
        }

        ksort($count_by_hours);

        return $count_by_hours;
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
