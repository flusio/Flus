<?php

namespace App\cli;

use Minz\Request;
use Minz\Response;

/**
 * @phpstan-import-type ResponseReturnable from Response
 * @phpstan-import-type ResponseGenerator from Response
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migrations extends \Minz\Migration\Controller
{
    /**
     * Reset the database.
     *
     * @request_param bool force
     *     Must be set to `true` or the command will fail.
     *
     * @response 400
     *     If the environment is production, or force is not true.
     * @response 200
     *     On success.
     *
     * @see \Minz\Migration\Controller::setup
     *
     * @return ResponseReturnable
     */
    public function reset(Request $request): mixed
    {
        $environment = \App\Configuration::$environment;
        if ($environment === 'production') {
            return Response::text(400, 'You can’t reset the database in production.');
        }

        $force = $request->parameters->getBoolean('force');
        if (!$force) {
            return Response::text(400, 'You must pass the --force option to confirm.');
        }

        \Minz\Database::drop();

        $migrations_version_path = static::migrationsVersionPath();
        @unlink($migrations_version_path);

        return $this->setup($request);
    }

    /**
     * Apply migration to setup UrlStatuses from existing read/bookmarks/never lists.
     *
     * @request_param int batch-size
     *     Size of batches, default to 1000.
     * @request_param bool dry-run
     *     Pass to not apply the migration and estimate data volume.
     *
     * @response 200
     *     On success.
     *
     * @return ResponseGenerator
     */
    public function setupUrlStatuses(Request $request): \Generator
    {
        $batch_size = $request->parameters->getInteger('batch-size', 1000);
        $dry_run = $request->parameters->getBoolean('dry-run');

        if ($batch_size <= 0 || $batch_size > 10000) {
            yield Response::text(400, '--batch-size is invalid, its value must be between 1 and 10000');
            return;
        }

        $total = 0;
        $offset = 0;

        $database = \Minz\Database::get();

        while (true) {
            $statement = $database->prepare(<<<SQL
                SELECT c.user_id, l.url_hash, c.type, lc.created_at
                FROM links_to_collections lc
                INNER JOIN collections c ON c.id = lc.collection_id
                INNER JOIN links l ON l.id = lc.link_id
                WHERE c.type IN ('read', 'bookmarks', 'never')
                ORDER BY c.user_id, l.url_hash
                LIMIT :batch_size
                OFFSET :offset
            SQL);

            $statement->execute([
                ':batch_size' => $batch_size,
                ':offset' => $offset,
            ]);

            $data = $statement->fetchAll();
            $data_size = count($data);

            if ($data_size === 0) {
                break;
            }

            $processed_data = [];

            foreach ($data as $row) {
                $key = "{$row['user_id']}#{$row['url_hash']}";

                if (isset($processed_data[$key])) {
                    $processed_row = $processed_data[$key];
                    $processed_row['created_at'] = min(
                        $processed_row['created_at'],
                        $row['created_at'],
                    );
                } else {
                    $processed_row = [
                        'user_id' => $row['user_id'],
                        'url_hash' => $row['url_hash'],
                        'created_at' => $row['created_at'],
                        'read_at' => null,
                        'read_later_at' => null,
                        'dismissed_at' => null,
                    ];
                }

                if ($row['type'] === 'read') {
                    $processed_row['read_at'] = $row['created_at'];
                } elseif ($row['type'] === 'bookmarks') {
                    $processed_row['read_later_at'] = $row['created_at'];
                } else {
                    $processed_row['dismissed_at'] = $row['created_at'];
                }

                $processed_data[$key] = $processed_row;
            }

            if (!$dry_run) {
                $values = [];
                $values_as_question_marks = [];

                foreach ($processed_data as $row) {
                    $values_as_question_marks[] = '(?, ?, ?, ?, ?, ?)';

                    $values[] = $row['created_at'];
                    $values[] = $row['user_id'];
                    $values[] = $row['url_hash'];
                    $values[] = $row['read_at'];
                    $values[] = $row['read_later_at'];
                    $values[] = $row['dismissed_at'];
                }

                $values_placeholder = implode(',', $values_as_question_marks);

                $statement = $database->prepare(<<<SQL
                    INSERT INTO url_statuses (
                        created_at,
                        user_id,
                        url_hash,
                        read_at,
                        read_later_at,
                        dismissed_at
                    )

                    VALUES {$values_placeholder}

                    ON CONFLICT (user_id, url_hash) DO UPDATE SET
                        created_at = LEAST(excluded.created_at, url_statuses.created_at),
                        read_at = COALESCE(excluded.read_at, url_statuses.read_at),
                        read_later_at = COALESCE(excluded.read_later_at, url_statuses.read_later_at),
                        dismissed_at = COALESCE(excluded.dismissed_at, url_statuses.dismissed_at)
                SQL);

                $statement->execute($values);
            }

            $total += $data_size;
            $offset += $batch_size;

            yield Response::text(200, "{$total} statuses migrated…");
        }

        yield Response::text(200, "Finished: {$total} statuses migrated.");
    }
}
