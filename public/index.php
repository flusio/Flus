<?php

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

// Setup the Minz framework
$app_path = realpath(__DIR__ . '/..');

include $app_path . '/autoload.php';
\Minz\Configuration::load('dotenv', $app_path);

// Get the http information and create a Request
$request = \Minz\Request::initFromGlobals();

// In development mode, all the requests are redirected to this file. However,
// if the file exists, we want to serve it as-is, which is done by returning
// false. It could be less hacky with Nginx, but I'd like to avoid to add
// another dependency as long as possible.
// @see https://www.php.net/manual/features.commandline.webserver.php
$current_filepath = $_SERVER['PHP_SELF'];
if (\Minz\Configuration::$environment === 'development' && $current_filepath !== '/index.php') {
    $public_path = $app_path . '/public';
    $filepath = realpath($public_path . $current_filepath);
    if (\flusio\utils\Belt::startsWith($filepath, $public_path) && file_exists($filepath)) {
        return false;
    }
}

// Initialize the Application and execute the request to get a Response
try {
    $application = new \flusio\Application();
    $response = $application->run($request);
} catch (\Exception $e) {
    $response = \Minz\Response::internalServerError('internal_server_error.phtml', [
        'environment' => \Minz\Configuration::$environment,
        'error' => $e,
    ]);
}

$is_head = strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD';
\Minz\Response::sendByHttp($response, echo_output: !$is_head);
