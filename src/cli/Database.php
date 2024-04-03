<?php

namespace App\cli;

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
     * @response 500
     *     If connection to the database fails
     * @response 200
     *     On success
     */
    public function status(): Response
    {
        try {
            $database = \Minz\Database::get();
            $result = $database->exec('SELECT 1');
            return Response::text(200, 'Database status: OK');
        } catch (\Minz\Errors\DatabaseError $e) {
            $status = $e->getMessage();
            return Response::text(500, 'Database status: ' . $status);
        }
    }
}
