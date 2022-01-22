<?php

namespace SpiderBits;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\MockHttpHelper;

    public function testGetWithParameters()
    {
        $http = new Http();
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
        $this->assertSame($expected_url, $data['url']);
        $this->assertSame($parameters['foo'], $data['args']['foo']);
        $this->assertSame($parameters['baz'], $data['args']['baz']);
    }

    public function testGetWithMixedParameters()
    {
        $http = new Http();
        $url = $this->fake('url') . '/get?foo=bar';
        $parameters = [
            'baz' => 'quz',
        ];
        $expected_url = $url . '&baz=quz';
        $this->mockHttpWithEcho($expected_url);

        $response = $http->get($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame($expected_url, $data['url']);
    }

    public function testGetWithAuthBasic()
    {
        $http = new Http();
        $user = 'john';
        $pass = 'secret';
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url, [], [
            'auth_basic' => $user . ':' . $pass,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame($user, $data['user']);
    }

    public function testGetWithSettingHeaders()
    {
        $http = new Http();
        $headers = [
            'X-Custom' => 'foo',
        ];
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url, [], [
            'headers' => $headers,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame('foo', $data['headers']['HTTP_X_CUSTOM']);
    }

    public function testGetWithSettingGlobalHeaders()
    {
        $http = new Http();
        $http->headers = [
            'X-Custom' => 'foo',
        ];
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame('foo', $data['headers']['HTTP_X_CUSTOM']);
    }

    public function testGetWithSettingUserAgent()
    {
        $http = new Http();
        $user_agent = $this->fake('userAgent');
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $http->get($url, [], [
            'user_agent' => $user_agent,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame($user_agent, $data['headers']['HTTP_USER_AGENT']);
    }

    public function testGetFailing()
    {
        $this->expectException(HttpError::class);
        $this->expectExceptionMessage('Could not resolve host: not-a-host');

        $http = new Http();

        $http->get('not-a-host');
    }

    public function testPostWithParameters()
    {
        $http = new Http();
        $url = $this->fake('url');
        $parameters = [
            'foo' => 'bar',
            'baz' => 'quz',
        ];
        $this->mockHttpWithEcho($url);

        $response = $http->post($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame($parameters['foo'], $data['form']['foo']);
        $this->assertSame($parameters['baz'], $data['form']['baz']);
    }
}
