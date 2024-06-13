<?php

namespace SpiderBits;

class ClearUrlsTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('clearProvider')]
    public function testClear(string $tracked_url, string $expected_url): void
    {
        $cleared_url = ClearUrls::clear($tracked_url);

        $this->assertSame($expected_url, $cleared_url);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function clearProvider(): array
    {
        return [
            // utm_* parameters in URL query
            [
                'https://example.com?utm_source=newsletter1&utm_medium=email&utm_campaign=sale',
                'https://example.com',
            ],
            // utm_* parameters in URL fragment
            [
                'https://example.com#utm_source=newsletter1&utm_medium=email&utm_campaign=sale',
                'https://example.com',
            ],
            // amazon parameters
            [
                'https://www.amazon.com/dp/exampleProduct/ref=sxin_0_pb?__mk_de_DE=ÅMÅŽÕÑ&keywords=tea&pd_rd_i=exampleProduct&pd_rd_r=8d39e4cd-1e4f-43db-b6e7-72e969a84aa5&pd_rd_w=1pcKM&pd_rd_wg=hYrNl&pf_rd_p=50bbfd25-5ef7-41a2-68d6-74d854b30e30&pf_rd_r=0GMWD0YYKA7XFGX55ADP&qid=1517757263&rnid=2914120011', // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://www.amazon.com/dp/exampleProduct',
            ],
            // amazon rawRule
            [
                'https://www.amazon.com/gp/exampleProduct/ref=as_li_ss_tl',
                'https://www.amazon.com/gp/exampleProduct',
            ],
            // google redirection
            [
                'https://www.google.com/url?q=https%3A%2F%2Fexample.com',
                'https://example.com/',
            ],
            // google redirection + utm parameters
            [
                'https://www.google.com/url?q=https%3A%2F%2Fexample.com%3Futm_source%3Dnewsletter1%26utm_medium%3Demail%26utm_campaign%3Dsale', // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/',
            ],
            // facebook redirection + fbclid
            [
                'https://l.facebook.com/l.php?u=https%3A%2F%2Fexample.com%2F%3Ffbclid%3Dfoo&h=bar',
                'https://example.com/'
            ],
            // humble bundle referral marketing
            [
                'https://humblebundle.com?partner=example',
                'https://humblebundle.com',
            ],
            // googlesyndication completeProvider (i.e. blocked)
            [
                'https://googlesyndication.com',
                '',
            ],
            // matrix.org exception
            [
                'https://matrix.org/_matrix/?referrer=example',
                'https://matrix.org/_matrix/?referrer=example',
            ],

            // safe cases (to verify it doesn't break normal URLs)
            [
                'https://example.com?id=1',
                'https://example.com?id=1',
            ],
            [
                'https://example.com?foo',
                'https://example.com?foo',
            ],
            [
                'https://example.com?foo=',
                'https://example.com?foo=',
            ],
            [
                'https://example.com?foo=bar&foo=baz',
                'https://example.com?foo=bar&foo=baz',
            ],
            [
                'https://example.com?id=1#foo',
                'https://example.com?id=1#foo',
            ],
            [
                'https://example.com?#',
                'https://example.com?#',
            ],
            [
                'https://example.com:8000',
                'https://example.com:8000',
            ],
        ];
    }
}
