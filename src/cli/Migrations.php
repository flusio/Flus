<?php

namespace flusio\cli;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migrations
{
    /**
     * Execute the migrations under src/migrations/. The version is stored in
     * the data/migrations_version.txt file.
     *
     * @response 500 if migrations fail
     * @response 200 on success
     */
    public function apply()
    {
        $app_path = \Minz\Configuration::$app_path;
        $data_path = \Minz\Configuration::$data_path;
        $migrations_path = $app_path . '/src/migrations';
        $migrations_version_path = $data_path . '/migrations_version.txt';

        $migration_version = @file_get_contents($migrations_version_path);
        if ($migration_version === false) {
            return Response::text(500, 'Cannot read the migrations version file.'); // @codeCoverageIgnore
        }

        $migrator = new \Minz\Migrator($migrations_path);
        $migration_version = trim($migration_version);
        if ($migration_version) {
            $migrator->setVersion($migration_version);
        }

        if ($migrator->upToDate()) {
            return Response::text(200, 'Your system is already up to date.');
        }

        $results = $migrator->migrate();

        $new_version = $migrator->version();
        $saved = @file_put_contents($migrations_version_path, $new_version);
        if ($saved === false) {
            $text = "Cannot save the migrations version file (version: {$version})."; // @codeCoverageIgnore
            return Response::text(500, $text); // @codeCoverageIgnore
        }

        $has_error = false;
        $results_as_text = [];
        foreach ($results as $migration => $result) {
            if ($result === false) {
                $result = 'KO';
            } elseif ($result === true) {
                $result = 'OK';
            }

            if ($result !== 'OK') {
                $has_error = true;
            }

            $results_as_text[] = "{$migration}: {$result}";
        }
        return Response::text($has_error ? 500 : 200, implode("\n", $results_as_text));
    }

    /**
     * Execute the rollback of the latest migrations.
     *
     * @request_param integer steps (default is 1)
     *
     * @response 500 if rollbacks fail
     * @response 200 on success
     */
    public function rollback($request)
    {
        $app_path = \Minz\Configuration::$app_path;
        $data_path = \Minz\Configuration::$data_path;
        $migrations_path = $app_path . '/src/migrations';
        $migrations_version_path = $data_path . '/migrations_version.txt';

        $migration_version = @file_get_contents($migrations_version_path);
        if ($migration_version === false) {
            return Response::text(500, 'Cannot read the migrations version file.'); // @codeCoverageIgnore
        }

        $migrator = new \Minz\Migrator($migrations_path);
        $migration_version = trim($migration_version);
        if ($migration_version) {
            $migrator->setVersion($migration_version);
        }

        $steps = $request->paramInteger('steps', 1);
        $results = $migrator->rollback($steps);

        $new_version = $migrator->version();
        $saved = @file_put_contents($migrations_version_path, $new_version);
        if ($saved === false) {
            $text = "Cannot save the migrations version file (version: {$version})."; // @codeCoverageIgnore
            return Response::text(500, $text); // @codeCoverageIgnore
        }

        $has_error = false;
        $results_as_text = [];
        foreach ($results as $migration => $result) {
            if ($result === false) {
                $result = 'KO';
            } elseif ($result === true) {
                $result = 'OK';
            }

            if ($result !== 'OK') {
                $has_error = true;
            }

            $results_as_text[] = "{$migration}: {$result}";
        }
        return Response::text($has_error ? 500 : 200, implode("\n", $results_as_text));
    }
}
