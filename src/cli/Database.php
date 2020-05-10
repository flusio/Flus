<?php

namespace flusio\cli;

use Minz\Response;

/**
 * Manipulate the database of the application.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Database
{
    /**
     * Return whether the database can be reached or not.
     *
     * @return \Minz\Response
     */
    public function status()
    {
        try {
            $database = \Minz\Database::get(false);
            $result = $database->exec('SELECT 1');
            return Response::text(200, 'Database status: OK');
        } catch (\Minz\Errors\DatabaseError $e) {
            $status = $e->getMessage();
            return Response::text(500, 'Database status: ' . $status);
        }
    }
}
