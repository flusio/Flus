<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table('url_statuses')]
class UrlStatus
{
    use Database\Recordable;
    use dao\BulkQueries;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $url_hash;

    #[Database\Column]
    public ?\DateTimeImmutable $read_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $read_later_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $dismissed_at = null;

    public function __construct(User $user, string $url)
    {
        $url = \SpiderBits\Url::sanitize($url);
        $this->user_id = $user->id;
        $this->url_hash = utils\Belt::hashUrl($url);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isReadLater(): bool
    {
        return $this->read_later_at !== null;
    }

    public function isDismissed(): bool
    {
        return $this->dismissed_at !== null;
    }

    /**
     * Mark the links as read for the user.
     *
     * @param Link|Link[] $links
     * @param positive-int $chunk_size
     */
    public static function markAsRead(User $user, Link|array $links, int $chunk_size = 500): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        $links = self::deduplicateByUrlHash($links);

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        foreach (array_chunk($links, $chunk_size) as $chunk_links) {
            $values_as_question_marks = [];
            $values = [];

            foreach ($chunk_links as $link) {
                $values_as_question_marks[] = '(?, ?, ?, ?)';
                $values = array_merge($values, [
                    $now->format(Database\Column::DATETIME_FORMAT),
                    $user->id,
                    $link->url_hash,
                    $now->format(Database\Column::DATETIME_FORMAT),
                ]);
            }
            $values_placeholder = implode(", ", $values_as_question_marks);

            $sql = <<<SQL
                INSERT INTO url_statuses (created_at, user_id, url_hash, read_at)
                VALUES {$values_placeholder}
                ON CONFLICT (user_id, url_hash) DO UPDATE SET
                    read_at = excluded.read_at,
                    read_later_at = NULL
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($values);
        }
    }

    /**
     * Unmark the links as read for the user.
     *
     * @param Link|Link[] $links
     * @param positive-int $chunk_size
     */
    public static function unmarkAsRead(User $user, Link|array $links, int $chunk_size = 500): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        $links = self::deduplicateByUrlHash($links);

        if (!$links) {
            return;
        }

        foreach (array_chunk($links, $chunk_size) as $chunk_links) {
            $values = [
                $user->id,
            ];
            $hashes_as_question_marks = [];

            foreach ($chunk_links as $link) {
                $hashes_as_question_marks[] = '?';
                $values[] = $link->url_hash;
            }
            $hashes_placeholder = implode(", ", $hashes_as_question_marks);

            $sql = <<<SQL
                UPDATE url_statuses
                SET read_at = NULL
                WHERE user_id = ?
                AND url_hash IN ({$hashes_placeholder})
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($values);
        }
    }

    /**
     * Mark the links to read later for the user.
     *
     * @param Link|Link[] $links
     * @param positive-int $chunk_size
     */
    public static function markAsReadLater(User $user, Link|array $links, int $chunk_size = 500): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        $links = self::deduplicateByUrlHash($links);

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        foreach (array_chunk($links, $chunk_size) as $chunk_links) {
            $values_as_question_marks = [];
            $values = [];

            foreach ($chunk_links as $link) {
                $values_as_question_marks[] = '(?, ?, ?, ?)';
                $values = array_merge($values, [
                    $now->format(Database\Column::DATETIME_FORMAT),
                    $user->id,
                    $link->url_hash,
                    $now->format(Database\Column::DATETIME_FORMAT),
                ]);
            }
            $values_placeholder = implode(", ", $values_as_question_marks);

            $sql = <<<SQL
                INSERT INTO url_statuses (created_at, user_id, url_hash, read_later_at)
                VALUES {$values_placeholder}
                ON CONFLICT (user_id, url_hash) DO UPDATE SET
                    read_later_at = excluded.read_later_at
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($values);
        }
    }

    /**
     * Mark the links as dismissed for the user.
     *
     * @param Link|Link[] $links
     * @param positive-int $chunk_size
     */
    public static function markAsDismissed(User $user, Link|array $links, int $chunk_size = 500): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        $links = self::deduplicateByUrlHash($links);

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        foreach (array_chunk($links, $chunk_size) as $chunk_links) {
            $values_as_question_marks = [];
            $values = [];

            foreach ($chunk_links as $link) {
                $values_as_question_marks[] = '(?, ?, ?, ?)';
                $values = array_merge($values, [
                    $now->format(Database\Column::DATETIME_FORMAT),
                    $user->id,
                    $link->url_hash,
                    $now->format(Database\Column::DATETIME_FORMAT),
                ]);
            }
            $values_placeholder = implode(", ", $values_as_question_marks);

            $sql = <<<SQL
                INSERT INTO url_statuses (created_at, user_id, url_hash, dismissed_at)
                VALUES {$values_placeholder}
                ON CONFLICT (user_id, url_hash) DO UPDATE SET
                    dismissed_at = excluded.dismissed_at
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($values);
        }
    }

    /**
     * Unmark the links for the user (aka remove the corresponding URL statuses).
     *
     * @param Link|Link[] $links
     * @param positive-int $chunk_size
     */
    public static function unmark(User $user, Link|array $links, int $chunk_size = 500): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        $links = self::deduplicateByUrlHash($links);

        if (!$links) {
            return;
        }

        foreach (array_chunk($links, $chunk_size) as $chunk_links) {
            $values_as_question_marks = [];
            $values = [$user->id];

            foreach ($chunk_links as $link) {
                $values_as_question_marks[] = '?';
                $values[] = $link->url_hash;
            }
            $values_placeholder = implode(", ", $values_as_question_marks);

            $sql = <<<SQL
                DELETE FROM url_statuses
                WHERE user_id = ?
                AND url_hash IN ({$values_placeholder})
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($values);
        }
    }

    /**
     * Deduplicate the given links based on their url_hash.
     *
     * Different links can share the same URL (e.g. two feeds publishing the
     * same link). Passing them twice to a same "ON CONFLICT DO UPDATE" query
     * would fail with a PostgreSQL "cannot affect row a second time" error.
     *
     * @param Link[] $links
     *
     * @return Link[]
     */
    private static function deduplicateByUrlHash(array $links): array
    {
        return array_values(array_column($links, null, 'url_hash'));
    }

    /**
     * Return the existing UrlStatus for this user and url if any, or build one otherwise.
     */
    public static function findOrBuild(User $user, string $url): self
    {
        $url_status = self::findBy([
            'user_id' => $user->id,
            'url_hash' => utils\Belt::hashUrl($url),
        ]);

        if (!$url_status) {
            $url_status = new self($user, $url);
        }

        return $url_status;
    }

    /**
     * Return the statuses of the given user for the given links, indexed by
     * the URL hashes of these links.
     *
     * The links unknown to the user have a new and unsaved status, as
     * findOrBuild would do.
     *
     * @param Link[] $links
     * @param positive-int $chunk_size
     *
     * @return array<string, self>
     */
    public static function listOrBuildByLinks(User $user, array $links, int $chunk_size = 1000): array
    {
        $links = self::deduplicateByUrlHash($links);

        $url_statuses_by_hashes = [];

        foreach (array_chunk($links, $chunk_size) as $chunk_links) {
            $url_statuses = self::listBy([
                'user_id' => $user->id,
                'url_hash' => array_column($chunk_links, 'url_hash'),
            ]);

            foreach ($url_statuses as $url_status) {
                $url_statuses_by_hashes[$url_status->url_hash] = $url_status;
            }
        }

        foreach ($links as $link) {
            if (!isset($url_statuses_by_hashes[$link->url_hash])) {
                $url_statuses_by_hashes[$link->url_hash] = new self($user, $link->url);
            }
        }

        return $url_statuses_by_hashes;
    }

    /**
     * @see dao\BulkQueries::bulkInsertOnConflict
     *
     * @return literal-string
     */
    public static function bulkInsertOnConflict(): string
    {
        return <<<SQL
            ON CONFLICT (user_id, url_hash) DO UPDATE SET
                created_at = LEAST(url_statuses.created_at, excluded.created_at),
                read_at = COALESCE(url_statuses.read_at, excluded.read_at),
                read_later_at = COALESCE(url_statuses.read_later_at, excluded.read_later_at),
                dismissed_at = COALESCE(url_statuses.dismissed_at, excluded.dismissed_at)
        SQL;
    }
}
