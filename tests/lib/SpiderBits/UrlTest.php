<?php

namespace SpiderBits;

class UrlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider absolutizeProvider
     */
    public function testAbsolutize($base_url, $url, $expected)
    {
        $absolutized_url = Url::absolutize($url, $base_url);

        $this->assertSame($expected, $absolutized_url);
    }

    /**
     * @dataProvider sanitizeProvider
     */
    public function testSanitize($input, $expected)
    {
        $sanitized_url = Url::sanitize($input);

        $this->assertSame($expected, $sanitized_url);
    }

    /**
     * @dataProvider parseAndBuildQueryProvider
     */
    public function testParseQuery($query, $expected_parameters)
    {
        $parameters = Url::parseQuery($query);

        $this->assertSame($expected_parameters, $parameters);
    }

    /**
     * @dataProvider parseAndBuildQueryProvider
     */
    public function testBuildQuery($expected_query, $parameters)
    {
        $query = Url::buildQuery($parameters);

        $this->assertSame($expected_query, $query);
    }

    public function absolutizeProvider()
    {
        return [
            ['https://host', '/path', 'https://host/path'],
            ['https://host', 'path', 'https://host/path'],
            ['https://host', 'https://example.com/path', 'https://example.com/path'],
            ['https://host/', 'path', 'https://host/path'],
            ['https://host/', '/path', 'https://host/path'],
            ['https://host/', '/path?foo', 'https://host/path?foo'],
            ['https://host/foo', '/path', 'https://host/path'],
            ['https://host/foo', 'path', 'https://host/path'],
            ['https://host/foo/', '/path', 'https://host/path'],
            ['https://host/foo/', 'path', 'https://host/path'],
            ['https://host/foo/bar', '/path', 'https://host/path'],
            ['https://host/foo/bar', 'path', 'https://host/foo/path'],
            ['https://host/foo?bar', '/path', 'https://host/path'],
            ['https://host/foo?bar', 'path', 'https://host/path'],
            ['https://host/foo/bar?baz', '/path', 'https://host/path'],
            ['https://host/foo/bar?baz', 'path', 'https://host/foo/path'],
            ['https://host:443/', '/path', 'https://host:443/path'],
            ['ftp://host', '//example.com/path', 'ftp://example.com/path'],
            ['//host', '//example.com/path', 'http://example.com/path'],
            ['', '/path', '/path'],
            ['http:///example.com', '/path', '/path'],
            ['http://:80', '/path', '/path'],
        ];
    }

    public function sanitizeProvider()
    {
        // This test suite comes from https://developers.google.com/safe-browsing/v4/urls-hashing#canonicalization
        // Minor differences are indicated in comments
        return [
            ["", ''],
            ["   ", ''],
            [" \n\t\r", ''],
            ["http://host/%25%32%35", 'http://host/%25'],
            ["http://host/%25%32%35%25%32%35", 'http://host/%25%25'],
            ["http://host/%2525252525252525", 'http://host/%25'],
            ["http://host/asdf%25%32%35asd", 'http://host/asdf%25asd'],
            ["http://host/%%%25%32%35asd%%", 'http://host/%25%25%25asd%25%25'],
            ["http://www.google.com/", 'http://www.google.com/'],
            ["http://%31%36%38%2e%31%38%38%2e%39%39%2e%32%36/%2E%73%65%63%75%72%65/%77%77%77%2E%65%62%61%79%2E%63%6F%6D/", 'http://168.188.99.26/.secure/www.ebay.com/'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["http://195.127.0.11/uploads/%20%20%20%20/.verify/.eBaysecure=updateuserdataxplimnbqmn-xplmvalidateinfoswqpcmlx=hgplmcx/", 'http://195.127.0.11/uploads/%20%20%20%20/.verify/.eBaysecure=updateuserdataxplimnbqmn-xplmvalidateinfoswqpcmlx=hgplmcx/'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["http://host%23.com/%257Ea%2521b%2540c%2523d%2524e%25f%255E00%252611%252A22%252833%252944_55%252B", 'http://host%23.com/~a!b@c%23d$e%25f^00&11*22(33)44_55+'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["http://3279880203/blah", 'http://195.127.0.11/blah'],
            ["http://www.google.com/blah/..", 'http://www.google.com/'],
            ["www.google.com/", 'http://www.google.com/'],
            ["www.google.com", 'http://www.google.com/'],
            // We want to keep the fragment
            ["http://www.evil.com/blah#frag", 'http://www.evil.com/blah#frag'],
            ["http://www.GOOgle.com/", 'http://www.google.com/'],
            ["http://www.google.com.../", 'http://www.google.com/'],
            ["http://www.google.com/foo\tbar\rbaz\n2",'http://www.google.com/foobarbaz2'],
            ["http://www.google.com/q?", 'http://www.google.com/q?'],
            ["http://www.google.com/q?r?", 'http://www.google.com/q?r%3F'],
            ["http://www.google.com/q?r?s", 'http://www.google.com/q?r%3Fs'],
            // We want to keep the fragment
            ["http://evil.com/foo#bar#baz", 'http://evil.com/foo#bar%23baz'],
            ["http://evil.com/foo;", 'http://evil.com/foo;'],
            ["http://evil.com/foo?bar;", 'http://evil.com/foo?bar%3B'],
            // idn_to_ascii cannot handle this url (which is invalid anyway)
            // and return an empty host
            ["http://\x01\x80.com/", 'http:///'],
            ["http://notrailingslash.com", 'http://notrailingslash.com/'],
            // We want to keep the port
            ["http://www.gotaport.com:1234/", 'http://www.gotaport.com:1234/'],
            ["  http://www.google.com/  ", 'http://www.google.com/'],
            ["http:// leadingspace.com/", 'http://%20leadingspace.com/'],
            ["http://%20leadingspace.com/", 'http://%20leadingspace.com/'],
            ["%20leadingspace.com/", 'http://%20leadingspace.com/'],
            ["https://www.securesite.com/", 'https://www.securesite.com/'],
            ["http://host.com/ab%23cd", 'http://host.com/ab%23cd'],
            ["http://host.com//twoslashes?more//slashes", 'http://host.com/twoslashes?more%2F%2Fslashes'],
            // More tests
            ['https://example.com/?url=http%3A%2F%2Fexample.fr%2F%3Ffoo%3Dbar&spam=egg', 'https://example.com/?url=http%3A%2F%2Fexample.fr%2F%3Ffoo%3Dbar&spam=egg'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ['https://example.com/?foo?=bar', 'https://example.com/?foo%3F=bar'],
            ['https://example.com/?foo%26=bar&spam=egg', 'https://example.com/?foo%26=bar&spam=egg'],
            ['https://example.com/?foo=bar?&foo=baz?', 'https://example.com/?foo=bar%3F&foo=baz%3F'],
            ["https://domén-with-accent.com?query=with-àccent", 'https://xn--domn-with-accent-dqb.com/?query=with-%C3%A0ccent'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["https://host.com?query=with-%C3%A0ccent", 'https://host.com/?query=with-%C3%A0ccent'],
            ["https://host.com?utm_source=gazette%252B-%252Babonn%25C3%25A9s", 'https://host.com/?utm_source=gazette%2B-%2Babonn%C3%A9s'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["https://host.com?utm_source=gazette%2B-%2Babonn%25C3%25A9s", 'https://host.com/?utm_source=gazette%2B-%2Babonn%C3%A9s'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["https://host.com?utm_source=gazette+-+abonn%C3%A9s", 'https://host.com/?utm_source=gazette%2B-%2Babonn%C3%A9s'], // phpcs:ignore Generic.Files.LineLength.TooLong
            ["http://evil.com/foo#bar/baz", 'http://evil.com/foo#bar/baz'],
            ["http://evil.com/foo#bar%22baz", 'http://evil.com/foo#bar%22baz'],
            ["http://evil.com/foo#🐘", 'http://evil.com/foo#%F0%9F%90%98'],
            ["?foo", 'http:///?foo'],
            ["http:///example.com", ''],
            ["http://:80", ''],
            ["http://user@:80", ''],
        ];
    }

    public function parseAndBuildQueryProvider()
    {
        return [
            [
                'foo=bar',
                ['foo' => 'bar'],
            ],
            [
                'foo=bar&spam=egg',
                ['foo' => 'bar', 'spam' => 'egg'],
            ],
            [
                'id=1',
                ['id' => '1'],
            ],
            [
                'foo',
                ['foo' => null],
            ],
            [
                'foo=',
                ['foo' => ''],
            ],
            [
                'foo=bar&foo=baz',
                ['foo' => ['bar', 'baz']],
            ],
            [
                'foo[]=bar&foo[]=baz',
                ['foo[]' => ['bar', 'baz']],
            ],
            [
                '',
                [],
            ],
        ];
    }
}
