<?php

namespace flusio\utils;

class IpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider maskProvider
     */
    public function testMask(string $ip, string $masked): void
    {
        $result = Ip::mask($ip);

        $this->assertSame($masked, $result);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function maskProvider(): array
    {
        return [
            ['2001:0db8:0000:08d3:0000:8a2e:0070:734a', '2001:0db8:0000:08d3:0000:8a2e:XXXX:XXXX'],
            ['207.142.131.5', '207.142.131.XXX'],
            ['2001:0db8::8d3::8a2e:7:734a', '2001:0db8::8d3::8a2e:XXXX:XXXX'],
            ['::1', ':XXXX:XXXX'],
            ['127.0.0.', '127.0.0.XXX'],
        ];
    }
}
