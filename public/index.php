<?php

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

// Setup the Minz framework
$app_path = realpath(__DIR__ . '/..');

assert($app_path !== false);

include $app_path . '/vendor/autoload.php';
\App\Configuration::load('dotenv', $app_path);

// Get the http information and create a Request
$request = \Minz\Request::initFromGlobals();

// Initialize the Application and execute the request to get a Response
try {
    $application = new \App\Application();
    $response = $application->run($request);
} catch (\Exception $e) {
    $response = \Minz\Response::internalServerError('internal_server_error.phtml', [
        'environment' => \App\Configuration::$environment,
        'error' => $e,
    ]);
}

$is_head = strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD';
\Minz\Response::sendByHttp($response, echo_output: !$is_head);
