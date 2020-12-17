<?php

namespace SpiderBits;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    public const TESTS_HOST = 'httpbin.org';

    /**
     * @dataProvider getProvider
     */
    public function testGet($url, $expected_code, $expected_text, $expected_headers)
    {
        $http = new Http();

        $response = $http->get($url);

        $this->assertSame($expected_code, $response->status);
        $this->assertStringContainsString($expected_text, $response->data);
        foreach ($expected_headers as $field_name => $field_value) {
            $this->assertArrayHasKey($field_name, $response->headers);
            $this->assertSame($field_value, $response->headers[$field_name]);
        }
    }

    public function testGetWithParameters()
    {
        $http = new Http();
        $url = 'https://' . self::TESTS_HOST . '/get';
        $parameters = [
            'foo' => 'bar',
            'baz' => 'quz',
        ];

        $response = $http->get($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame($url . '?foo=bar&baz=quz', $data['url']);
    }

    public function testGetWithMixedParameters()
    {
        $http = new Http();
        $url = 'https://' . self::TESTS_HOST . '/get?foo=bar';
        $parameters = [
            'baz' => 'quz',
        ];

        $response = $http->get($url, $parameters);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertSame($url . '&baz=quz', $data['url']);
    }

    public function testGetWithAuthBasic()
    {
        $http = new Http();
        $user = 'john';
        $pass = 'secret';
        $url = 'https://' . self::TESTS_HOST . "/basic-auth/{$user}/{$pass}";

        $response = $http->get($url, [], [
            'auth_basic' => $user . ':' . $pass,
        ]);

        $this->assertSame(200, $response->status);
        $data = json_decode($response->data, true);
        $this->assertTrue($data['authenticated']);
        $this->assertSame($user, $data['user']);
    }

    public function getProvider()
    {
        return [
            [
                'https://' . self::TESTS_HOST . '/get',
                200,
                self::TESTS_HOST,
                ['content-type' => 'application/json'],
            ],
            [
                'https://' . self::TESTS_HOST . '/status/404',
                404,
                '',
                ['content-type' => 'text/html; charset=utf-8'],
            ],
            [
                // redirections seem to fail on httpbin.org, so test let's test
                // with my own domain (http is redirected to https)
                'http://flus.fr/',
                200,
                'Flus, média social de veille',
                ['content-type' => 'text/html;charset=UTF-8'],
            ],
            [
                'not a url',
                0,
                '',
                [],
            ],
        ];
    }
}
