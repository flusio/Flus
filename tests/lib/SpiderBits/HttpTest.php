<?php

namespace SpiderBits;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\HttpHelper;

    public function testGetWithParameters(): void
    {
        $http = new Http();
        /** @var non-empty-string */
        $url = $this->fake('url');
        $parameters = [
            'foo' => 'bar',
            'baz' => 'quz',
        ];
        $expected_url = $url . '?foo=bar&baz=quz';
        $this->mockHttpWithEcho($expected_url);

        $response = $http->get($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertSame($expected_url, $data['url']);
        $this->assertSame($parameters['foo'], $data['args']['foo']);
        $this->assertSame($parameters['baz'], $data['args']['baz']);
    }

    public function testGetWithMixedParameters(): void
    {
        $http = new Http();
        /** @var non-empty-string */
        $url = $this->fake('url');
        $url = $url . '/get?foo=bar';
        $parameters = [
            'baz' => 'quz',
        ];
        $expected_url = $url . '&baz=quz';
        $this->mockHttpWithEcho($expected_url);

        $response = $http->get($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertSame($expected_url, $data['url']);
    }

    public function testGetWithAuthBasic(): void
    {
        $http = new Http();
        $user = 'john';
        $pass = 'secret';
        /** @var non-empty-string */
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url, [], [
            'auth_basic' => $user . ':' . $pass,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertSame($user, $data['user']);
    }

    public function testGetWithSettingHeaders(): void
    {
        $http = new Http();
        $headers = [
            'X-Custom' => 'foo',
        ];
        /** @var non-empty-string */
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url, [], [
            'headers' => $headers,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['headers']);
        $this->assertSame('foo', $data['headers']['HTTP_X_CUSTOM']);
    }

    public function testGetWithSettingGlobalHeaders(): void
    {
        $http = new Http();
        $http->headers = [
            'X-Custom' => 'foo',
        ];
        /** @var non-empty-string */
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['headers']);
        $this->assertSame('foo', $data['headers']['HTTP_X_CUSTOM']);
    }

    public function testGetWithSettingUserAgent(): void
    {
        $http = new Http();
        /** @var non-empty-string */
        $user_agent = $this->fake('userAgent');
        /** @var non-empty-string */
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url, [], [
            'user_agent' => $user_agent,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['headers']);
        $this->assertSame($user_agent, $data['headers']['HTTP_USER_AGENT']);
    }

    public function testGetFailing(): void
    {
        $this->expectException(HttpError::class);
        $this->expectExceptionMessage('Could not resolve host: not-a-host');

        $http = new Http();

        $http->get('not-a-host');
    }

    public function testPostWithParameters(): void
    {
        $http = new Http();
        /** @var non-empty-string */
        $url = $this->fake('url');
        $parameters = [
            'foo' => 'bar',
            'baz' => 'quz',
        ];
        $this->mockHttpWithEcho($url);

        $response = $http->post($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['form']);
        $this->assertSame($parameters['foo'], $data['form']['foo']);
        $this->assertSame($parameters['baz'], $data['form']['baz']);
    }

    public function testPostWithJsonParameters(): void
    {
        $http = new Http();
        /** @var non-empty-string */
        $url = $this->fake('url');
        $parameters = [
            'foo' => 'bar',
            'baz' => 'quz',
        ];
        $this->mockHttpWithEcho($url);

        $response = $http->post($url, $parameters, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['php://input']);

        $input_data = json_decode($data['php://input'], associative: true);
        $this->assertIsArray($input_data);
        $this->assertSame($parameters['foo'], $input_data['foo']);
        $this->assertSame($parameters['baz'], $input_data['baz']);
    }
}
