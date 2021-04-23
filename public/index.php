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
$http_parameters = array_merge($_GET, $_POST, $_FILES);
$headers = array_merge($_SERVER, [
    'COOKIE' => $_COOKIE,
]);

$request = new \Minz\Request($http_method, $http_uri, $http_parameters, $headers);

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
$application = new \flusio\Application();
$response = $application->run($request);
$response->setHeader('Turbolinks-Location', $http_uri);
$response->setHeader('Permissions-Policy', 'interest-cohort=()'); // @see https://cleanuptheweb.org/

// Generate the HTTP headers, cookies and output
http_response_code($response->code());

foreach ($response->cookies() as $cookie) {
    setcookie($cookie['name'], $cookie['value'], $cookie['options']);
}

foreach ($response->headers() as $header) {
    header($header);
}

if ($request_method !== 'head') {
    echo $response->render();
}
