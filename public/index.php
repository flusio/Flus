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

// Initialize the Application and execute the request to get a Response
try {
    $application = new \App\Application();

    $request = \Minz\Request::initFromGlobals();

    $response = $application->run($request);
} catch (\Minz\Errors\RequestError $e) {
    $response = \Minz\Response::notFound('not_found.phtml', [
        'error' => $e,
    ]);
} catch (\Exception $e) {
    $response = \Minz\Response::internalServerError('internal_server_error.phtml', [
        'error' => $e,
    ]);
}

$is_head = strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD';
\Minz\Response::sendByHttp($response, echo_output: !$is_head);
