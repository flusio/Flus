<?php

namespace SpiderBits;

class HttpTest extends \PHPUnit\Framework\TestCase
{
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

    public function getProvider()
    {
        return [
            [
                'https://flus.fr/',
                200,
                'Flus, média social citoyen',
                ['content-type' => 'text/html;charset=UTF-8'],
            ],
            [
                'http://flus.fr/',
                200,
                'Flus, média social citoyen',
                ['content-type' => 'text/html;charset=UTF-8'],
            ],
            [
                'https://flus.fr/does_not_exist.html',
                404,
                '',
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
