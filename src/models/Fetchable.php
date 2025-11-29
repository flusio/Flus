<?php

namespace App\models;

use Minz\Database;

/**
 * @phpstan-import-type Serie from \App\jobs\traits\JobInSerie
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Fetchable
{
    // These parameters allow to refetch the Fetchable models in error for about ~6 days.
    public const FETCHED_RETRIES_MIN_SECONDS = 60;
    public const FETCHED_RETRIES_EXPONENT = 4;
    public const FETCHED_RETRIES_MAX_TRIES = 9;
    public const FETCHED_RETRIES_CODES = [
        408, // Request Time-out
        425, // Too Early
        429, // Too Many Requests
        500, // Internal Server Error
        503, // Service Unavailable
        504, // Gateway Time-out
        509, // Bandwidth Limit Exceeded (non-standard - Apache)
        520, // Unknown Error (non-standard - Cloudflare)
        521, // Web Server Is Down (non-standard - Cloudflare)
        522, // Connection Timed Out (non-standard - Cloudflare)
        523, // Origin Is Unreachable (non-standard - Cloudflare)
        524, // A Timeout Occurred (non-standard - Cloudflare)
    ];

    #[Database\Column]
    public ?\DateTimeImmutable $fetched_at = null;

    #[Database\Column]
    public int $fetched_code = 0;

    #[Database\Column]
    public ?string $fetched_error = null;

    #[Database\Column]
    public int $fetched_count = 0;

    #[Database\Column]
    public ?\DateTimeImmutable $fetched_retry_at = null;

    // This variable is deprecated and should no longer be used.
    #[Database\Column]
    public bool $to_be_fetched = true;

    /**
     * Changes the fetching information of the Fetchable model.
     */
    public function fetch(
        int $code,
        ?string $error = null,
        ?\DateTimeImmutable $retry_after = null,
    ): void {
        $this->fetched_at = \Minz\Time::now();
        $this->fetched_code = $code;
        $this->fetched_error = $error;

        // shouldBeFetchedLater() depends on fetched_count, which *must* be
        // incremented *before*.
        $this->fetched_count = $this->fetched_count + 1;

        $to_be_fetched_again = $this->shouldBeFetchedAgain();

        if ($to_be_fetched_again && $retry_after) {
            $this->fetched_retry_at = max($this->fetchAgainAfter(), $retry_after);
        } elseif ($to_be_fetched_again) {
            $this->fetched_retry_at = $this->fetchAgainAfter();
        } else {
            $this->fetched_retry_at = null;
        }
    }

    /**
     * Returns the date after which we can fetch the model again, or null if it
     * shouldn't be fetched.
     */
    public function fetchAgainAfter(): ?\DateTimeImmutable
    {
        if (!$this->shouldBeFetchedAgain()) {
            return null;
        }

        $retry_after = self::FETCHED_RETRIES_MIN_SECONDS;
        $retry_after += pow($this->fetched_count - 1, self::FETCHED_RETRIES_EXPONENT);

        return \Minz\Time::fromNow($retry_after, 'seconds');
    }

    /**
     * Returns whether the model should fetched again or not.
     */
    public function shouldBeFetchedAgain(): bool
    {
        $never_fetched = $this->fetched_at === null;
        if ($never_fetched) {
            return true;
        }

        $server_in_error = in_array($this->fetched_code, self::FETCHED_RETRIES_CODES);
        $reached_max_retries = $this->fetched_count >= self::FETCHED_RETRIES_MAX_TRIES;

        return $server_in_error && !$reached_max_retries;
    }

    /**
     * Return a list of models to fetch.
     *
     * A "serie" can be passed in order to only return the models with an id
     * matching the serie. This allows to fetch the models with several jobs in
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
        $now = \Minz\Time::now();
        $parameters = [
            ':now' => $now->format(Database\Column::DATETIME_FORMAT),
            ':max' => $max,
        ];

        $clause_serie = '';
        if ($serie && $serie['total'] > 1) {
            $clause_serie = 'AND MOD(id::bigint, :total_number_series) = :serie_number';
            $parameters[':total_number_series'] = $serie['total'];
            $parameters[':serie_number'] = $serie['number'];
        }

        $table_name = self::tableName();

        $sql = <<<SQL
            SELECT * FROM {$table_name}

            WHERE (
                fetched_at IS NULL
                OR (fetched_retry_at IS NOT NULL AND fetched_retry_at <= :now)
            )

            {$clause_serie}

            ORDER BY fetched_at NULLS FIRST
            LIMIT :max
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the number of models to fetch.
     */
    public static function countToFetch(): int
    {
        $table_name = self::tableName();
        $sql = <<<SQL
            SELECT COUNT(*) FROM {$table_name}

            WHERE fetched_at IS NULL
            OR (
                fetched_retry_at IS NOT NULL
                AND fetched_retry_at <= :now
            )
        SQL;

        $now = \Minz\Time::now();

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $now->format(Database\Column::DATETIME_FORMAT),
        ]);

        return intval($statement->fetchColumn());
    }
}
