<?php

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

// Setup the Minz framework
$app_path = realpath(__DIR__ . '/..');

include $app_path . '/autoload.php';
\Minz\Configuration::load('dotenv', $app_path);
\Minz\Environment::initialize();
\Minz\Environment::startSession();

// Get the http information and create a Request
$request_method = strtolower($_SERVER['REQUEST_METHOD']);
$http_method = $request_method === 'head' ? 'get' : $request_method;
$http_uri = $_SERVER['REQUEST_URI'];
$http_parameters = array_merge($_GET, $_POST);

$request = new \Minz\Request($http_method, $http_uri, $http_parameters, $_SERVER);

// Initialize the Application and execute the request to get a Response
$application = new \flusio\Application();
$response = $application->run($request);

// Generate the HTTP headers and output
http_response_code($response->code());
foreach ($response->headers() as $header) {
    header($header);
}

if ($request_method !== 'head') {
    echo $response->render();
}
