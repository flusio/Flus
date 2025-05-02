<?php

namespace tests;

use App\auth;
use App\models;
use Minz\Errors;
use Minz\ParameterBag;
use Minz\Request;
use Minz\Response;
use Minz\Tests;
use tests\factories\UserFactory;

/**
 * @phpstan-import-type ModelValues from \Minz\Database\Recordable
 * @phpstan-import-type RequestMethod from Request
 * @phpstan-import-type ResponseReturnable from Response
 * @phpstan-import-type Parameters from ParameterBag
 */
trait ApiHelper
{
    use Tests\ApplicationHelper;
    use Tests\ResponseAsserts;

    /**
     * Create a request based on the parameters, and run it over the
     * $application.
     *
     * It encodes the parameters in JSON and pass them as the `@input`
     * parameter. It also adds the `Content-Type: application/json` header if
     * it's not already set.
     *
     * @param RequestMethod $method
     * @param Parameters $parameters
     * @param Parameters $headers
     * @param Parameters $cookies
     * @param Parameters $server
     *
     * @return ResponseReturnable
     *
     * @throws Errors\RuntimeException
     *     Raised if the Application class doesn't exist.
     */
    public function apiRun(
        string $method,
        string $uri,
        array $parameters = [],
        array $headers = [],
        array $cookies = [],
        array $server = [],
    ): mixed {
        $parameters = [
            '@input' => json_encode($parameters),
        ];

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return self::appRun($method, $uri, $parameters, $headers, $cookies, $server);
    }

    /**
     * Simulate a user who logs in. A User is created using a DatabaseFactory.
     *
     * @param ModelValues $user_values
     */
    public function login(array $user_values = []): models\User
    {
        $user = UserFactory::create($user_values);

        $session = auth\CurrentUser::createApiSession($user, 'test app');

        return $user;
    }

    /**
     * Simulate a user who logs out. It is called before each test to make sure
     * to reset the context.
     */
    #[\PHPUnit\Framework\Attributes\Before]
    public function logout(): void
    {
        auth\CurrentUser::deleteSession();
    }

    /**
     * @param ResponseReturnable $response
     * @param mixed[] $json
     */
    public function assertApiResponse(mixed $response, array $json): void
    {
        $encoded_json = json_encode($json);
        $this->assertNotFalse($encoded_json, 'The given parameter is not an encodable array');
        $this->assertResponseEquals($response, $encoded_json);
    }

    /**
     * @param ResponseReturnable $response
     * @param array{string, string} $error
     */
    public function assertApiError(mixed $response, string $field, array $error): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $content = $response->render();
        $json = json_decode($content, associative: true);
        $this->assertIsArray($json, 'The response does not render a valid JSON array');
        $this->assertArrayHasKey('errors', $json, 'The JSON response does not contain an "errors" key');
        $errors = $json['errors'];
        $this->assertIsArray($errors, 'The errors is not a valid JSON array');
        $this->assertArrayHasKey($field, $errors, "The JSON errors does not contain an \"{$field}\" key");
        $field_errors = $errors[$field];
        $this->assertIsArray($field_errors, "The {$field} error field is not a valid JSON array");

        $has_error = false;
        foreach ($field_errors as $field_error) {
            $this->assertIsArray($field_error, "The {$field} JSON error is not a valid JSON array");
            $this->assertArrayHasKey(
                'code',
                $field_error,
                "The {$field} JSON error must contain a \"code\" key"
            );
            $this->assertArrayHasKey(
                'description',
                $field_error,
                "The {$field} JSON error must contain a \"description\" key"
            );

            if (
                $field_error['code'] === $error[0] &&
                $field_error['description'] === $error[1]
            ) {
                $has_error = true;
                break;
            }
        }

        $this->assertTrue(
            $has_error,
            "The {$field} JSON errors do not contain the [{$error[0]}, {$error[1]}] error"
        );
    }
}
