<?php

/**
 * The mock server allows to test HTTP requests without network. It is used
 * with the mockHttpWith* methods from the MockHttpHelper.
 *
 * The mock server is started via the docker-compose file in development and in
 * the CI workflow on GitHub Actions with the following command:
 *
 *     php -t . -S 0.0.0.0:8001 ./tests/mock_server.php
 *
 * The mock host is then declared in the configuration file of the test
 * environment via the MOCK_HOST environment variable. It is passed to the
 * \SpiderBits\Http::$mock_host when calling a mockHttpWith* methods.
 *
 * The server can answer in 3 different ways:
 *
 * - with a full HTTP response (i.e. including headers and body);
 * - with a file from the filesystem;
 * - by echoing the information about the HTTP request.
 *
 * To start using the mock server, the \SpiderBits\Http::$mock_host is set to
 * point to the mock host. All requests are transfered to the mock server then.
 * A request to a URL must be mocked first by telling the server how to answer.
 * To mock a request, make a call to the server with the following parameters:
 *
 * - url: the URL to mock;
 * - action: it must be set to `mock`;
 * - mock: a string indicating how to answer, it can either be a full HTTP
 *   response body, a path to a file (under app_path), or the 'echo' string.
 *
 * It can be used with the mockHttpWith* methods this way:
 *
 *     $this->mockHttpWithResponse('https://flus.fr/carnet', <<<TEXT
 *         HTTP/1.0 200
 *         Content-type: text/html
 *
 *         <html>
 *             <head>
 *                 <title>Carnet de Flus</title>
 *                 <meta property="og:image" content="https://flus.fr/carnet/card.png" />
 *             </head>
 *         </html>
 *         TEXT
 *     );
 *     $this->mockHttpWithFile('https://flus.fr/carnet/card.png', 'public/static/og-card.png');
 *
 * The server will then answer to the mocked URLs with the declared mocks. If
 * you didn't declare any mock, the server will answer with a HTTP status code
 * set to 0 (could not resolve).
 *
 * You should clear the mocks at the end of each test by calling the server
 * with the `action` parameter set to `clear`. It is automatically done by the
 * MockHttpHelper.
 *
 * The design of the server isn't very good, but it is quite easy to use and
 * understand so I think it's good enough.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

$app_path = realpath(__DIR__ . '/../..');

include $app_path . '/autoload.php';
\Minz\Configuration::load('test', $app_path);
\Minz\Configuration::$no_syslog_output = false;
\Minz\Environment::initialize();

$http_parameters = array_merge($_GET, $_POST);

$mocks_path = sys_get_temp_dir() . '/flusio/mocks';
@mkdir($mocks_path, 0777, true);

$url = urldecode($http_parameters['url'] ?? '');
$url_base64 = str_replace('/', '', base64_encode($url));
$mock_path = "{$mocks_path}/{$url_base64}";

// No action parameter? Serve the URL with a mock if any.
if (!isset($http_parameters['action'])) {
    \Minz\Log::notice("SERVING {$url} AS {$mock_path}");

    if (!file_exists($mock_path)) {
        \Minz\Log::notice('MOCK DOES NOT EXIST');

        http_response_code(0);

        echo "Could not resolve: {$url}";

        return;
    }

    // We try to parse the content of the mock as a SpiderBits Response. If it
    // fails, the status code will be set to 0.
    $content = @file_get_contents($mock_path);
    $response = \SpiderBits\Response::fromText($content);

    if ($response->status > 0) {
        // The status is greater than 0, it means the mock *is* a HTTP
        // response, so we can send it back.

        \Minz\Log::notice("RESPONSE CODE {$response->status}");

        http_response_code($response->status);

        foreach ($response->headers as $field_name => $field_content) {
            header("{$field_name}: {$field_content}");
        }

        echo $response->data;

        return;
    }

    if ($content === 'echo') {
        // If it's not a HTTP response, maybe content is equal to echo? In this
        // case, we return the info about the request being made as JSON. It's
        // useful to explore headers and parameters sent by the HTTP client.

        \Minz\Log::notice('ECHO');

        $info = [
            'url' => $url,
            'headers' => [],
            'origin' => $_SERVER['REMOTE_ADDR'],
            'args' => [],
        ];

        $parsed_url = parse_url($url);
        if (isset($parsed_url['query'])) {
            $query = $parsed_url['query'];
            $args = [];
            parse_str($query, $args);
            $info['args'] = $args;
        }

        foreach ($_SERVER as $key => $value) {
            if (\flusio\utils\Belt::startsWith($key, 'HTTP_')) {
                $info['headers'][$key] = $value;
            } elseif ($key === 'PHP_AUTH_USER') {
                $info['user'] = $value;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $info['form'] = $_POST;
        }

        header('Content-type: application/json');

        echo json_encode($info);

        return;
    }

    // As fallback, we consider that $content is the path to a file under
    // app_path.
    $requested_filename = $app_path . '/' . $content;
    $filename = realpath($requested_filename);

    if (!$filename || !\flusio\utils\Belt::startsWith($filename, $app_path)) {
        // The file doesn't exist, or is not under the app_path so we consider
        // the mock is invalid.
        \Minz\Log::notice("FILE {$requested_filename} DOES NOT EXIST");

        http_response_code(0);

        echo "Could not resolve: {$url}";

        return;
    }

    $mime_type = mime_content_type($filename);

    \Minz\Log::notice("FILE {$requested_filename} ({$mime_type})");

    http_response_code(200);
    header("Content-Type: {$mime_type}");
    readfile($filename);

    return;
}

// The request asks the server to mock a URL.
if ($http_parameters['action'] === 'mock') {
    \Minz\Log::notice("MOCK {$url} AS {$mock_path}");

    $mock = $http_parameters['mock'] ?? '';
    file_put_contents($mock_path, $mock);

    return;
}

// The request asks the server to clear the mocks.
if ($http_parameters['action'] === 'clear') {
    \Minz\Log::notice('MOCK CLEAR');

    foreach (scandir($mocks_path) as $filename) {
        if ($filename[0] === '.') {
            continue;
        }

        $mock_path = "{$mocks_path}/{$filename}";
        @unlink($mock_path);
    }

    return;
}
