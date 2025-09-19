<?php

namespace App\models\dao\collections;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the Fetcher.
 *
 * @phpstan-import-type Serie from \App\jobs\traits\JobInSerie
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FetcherQueries
{
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
}
