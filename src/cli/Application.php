<?php

namespace flusio\cli;

/**
 * This is the central class for the CLI. It is called from the cli file.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Application
{
    /** @var \Minz\Engine **/
    private $engine;

    /**
     * Setup a Router and declare its routes.
     */
    public function __construct()
    {
        // Initialize the routes
        $router = new \Minz\Router();
        $router->addRoute('cli', '/', 'cli/System#usage');
        $router->addRoute('cli', '/system/secret', 'cli/System#secret');
        $router->addRoute('cli', '/system/setup', 'cli/System#setup');
        $router->addRoute('cli', '/system/rollback', 'cli/System#rollback');
        $router->addRoute('cli', '/database/status', 'cli/Database#status');
        $router->addRoute('cli', '/users/create', 'cli/Users#create');
        $router->addRoute('cli', '/users/clean', 'cli/Users#clean');

        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);
    }

    /**
     * Execute a request.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function run($request)
    {
        return $this->engine->run($request);
    }
}
