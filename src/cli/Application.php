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
            'not_found_template' => 'cli/not_found.txt',
            'internal_server_error_template' => 'cli/internal_server_error.txt',
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
        $cli_locale = \App\Configuration::$application['cli_locale'];
        if ($cli_locale) {
            utils\Locale::setCurrentLocale($cli_locale);
        }

        $bin = $request->parameters->getString('bin', 'php cli');
        $bin = $bin === 'cli' ? 'php cli' : $bin;

        $current_command = $request->path();
        $current_command = trim(str_replace('/', ' ', $current_command));

        \Minz\Template\Simple::addGlobals([
            'bin' => $bin,
            'current_command' => $current_command,
        ]);

        return \Minz\Engine::run($request);
    }
}
