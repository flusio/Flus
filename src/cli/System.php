<?php

namespace flusio\cli;

use Minz\Response;

/**
 * Manipulate the system to setup the application.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class System
{
    public function usage()
    {
        $usage = 'Usage: php ./cli --request REQUEST [-pKEY=VALUE]...' . "\n\n";
        $usage .= 'REQUEST can be one of the following:' . "\n";
        $usage .= '  /                 Show this help' . "\n";
        $usage .= '  /database/status  Return the status of the DB connection' . "\n";
        $usage .= '  /system/setup     Initialize or update the system' . "\n";
        $usage .= '  /users/clean      Clean not validated users created NUMBER months ago' . "\n";
        $usage .= '      [-psince=NUMBER] where NUMBER is the number of months, greater than 0 (default is 1)';

        return Response::text(200, $usage);
    }

    /**
     * Call init or migrate depending on the presence of a migrations version file.
     *
     * @return \Minz\Response
     */
    public function setup()
    {
        $data_path = \Minz\Configuration::$data_path;
        $migrations_version_path = $data_path . '/migrations_version.txt';

        if (file_exists($migrations_version_path)) {
            return $this->migrate();
        } else {
            return $this->init();
        }
    }

    /**
     * Initialize the database and set the migration version.
     *
     * @return \Minz\Response
     */
    private function init()
    {
        $app_path = \Minz\Configuration::$app_path;
        $data_path = \Minz\Configuration::$data_path;
        $schema_path = $app_path . '/src/schema.sql';
        $migrations_path = $app_path . '/src/migrations';
        $migrations_version_path = $data_path . '/migrations_version.txt';

        \Minz\Database::reset();

        $schema = file_get_contents($schema_path);
        if ($schema) {
            $database = \Minz\Database::get();
            $result = $database->exec($schema);
            if ($result === false) {
                return Response::text(500, 'The database schema couldn’t be loaded.'); // @codeCoverageIgnore
            }
        }

        $migrator = new \Minz\Migrator($migrations_path);
        $version = $migrator->lastVersion();
        $saved = @file_put_contents($migrations_version_path, $version);
        if ($saved === false) {
            return Response::text(500, 'Cannot create the migrations version file.'); // @codeCoverageIgnore
        }

        return Response::text(200, 'The system has been initialized.');
    }

    /**
     * Execute the migrations under src/migrations/. The version is stored in
     * the data/migrations_version.txt file.
     *
     * @return \Minz\Response
     */
    private function migrate()
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
        $text = '';
        foreach ($results as $migration => $result) {
            if ($result === false) {
                $result = 'KO';
            } elseif ($result === true) {
                $result = 'OK';
            }

            if ($result !== 'OK') {
                $has_error = true;
            }

            $text .= "\n" . $migration . ': ' . $result;
        }
        return Response::text($has_error ? 500 : 200, $text);
    }
}
