<?php

namespace flusio\cli;

use Minz\Request;
use Minz\Response;

/**
 * @phpstan-import-type ResponseReturnable from Response
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
        $environment = \Minz\Configuration::$environment;
        if ($environment === 'production') {
            return Response::text(400, 'You can’t reset the database in production.');
        }

        $force = $request->paramBoolean('force', false);
        if (!$force) {
            return Response::text(400, 'You must pass the --force option to confirm.');
        }

        \Minz\Database::drop();

        $migrations_version_path = static::migrationsVersionPath();
        @unlink($migrations_version_path);

        return $this->setup($request);
    }
}
