<?php

namespace App\cli;

use Minz\Request;
use Minz\Response;
use App\utils;

/**
 * This is the central class for the CLI. It is called from the cli file.
 *
 * @phpstan-import-type ResponseReturnable from Response
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Application
{
    /**
     * Setup the Engine.
     */
    public function __construct()
    {
        $router = \App\Router::loadCli();
        \Minz\Engine::init($router, [
            'start_session' => false,
            'not_found_view_pointer' => 'cli/not_found.txt',
            'internal_server_error_view_pointer' => 'cli/internal_server_error.txt',
            'controller_namespace' => '\\App\\cli',
        ]);
    }

    /**
     * Execute a request.
     *
     * @return ResponseReturnable
     */
    public function run(Request $request): mixed
    {
        /** @var string */
        $cli_locale = \Minz\Configuration::$application['cli_locale'];
        if ($cli_locale) {
            utils\Locale::setCurrentLocale($cli_locale);
        }

        $bin = $request->param('bin', 'php cli');
        $bin = $bin === 'cli' ? 'php cli' : $bin;

        $current_command = $request->path();
        $current_command = trim(str_replace('/', ' ', $current_command));

        \Minz\Output\View::declareDefaultVariables([
            'bin' => $bin,
            'current_command' => $current_command,
        ]);

        return \Minz\Engine::run($request);
    }
}
