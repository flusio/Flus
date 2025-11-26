<?php

namespace App\cli;

use App\utils;
use Minz\Request;
use Minz\Response;

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

        $command = $request->path();
        $command = trim(str_replace('/', ' ', $command));

        \Minz\Template\Twig::addGlobals([
            'app' => [
                'brand' => \App\Configuration::$application['brand'],
                'version' => \App\Configuration::$application['version'],
                'user_agent' => utils\UserAgent::get(),
                'bin' => $bin,
                'command' => $command,
            ],
        ]);


        return \Minz\Engine::run($request);
    }
}
