<?php

namespace SpiderBits;

class HtmlSanitizerTest extends \PHPUnit\Framework\TestCase
{
    public function testSanitize(): void
    {
        $html_sanitizer = new HtmlSanitizer();
        $dirty_html = <<<HTML
            <div>Hello</div>
            <div>World!</div>
            <script>alert('hello 👀');</script>
            HTML;
        $expected_html = <<<HTML
            <div>Hello</div>
            <div>World!</div>
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    public function testSanitizeWithSpecificAllowedElements(): void
    {
        $html_sanitizer = new HtmlSanitizer([
            'span' => [],
        ]);
        $dirty_html = <<<HTML
            <div>Hello</div>
            <span>World!</span>
            <script>alert('hello 👀');</script>
            HTML;
        $expected_html = <<<HTML
            <span>World!</span>
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    public function testSanitizeWithBlockedElements(): void
    {
        $html_sanitizer = new HtmlSanitizer(null, [
            'div'
        ]);
        $dirty_html = <<<HTML
            <div>
                Hello World!
            </div>
            HTML;
        $expected_html = <<<HTML
            Hello World!
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    public function testSanitizeWithNonAsciiChars(): void
    {
        $html_sanitizer = new HtmlSanitizer();
        $dirty_html = <<<HTML
            <p>Ça marche ?</p>
            HTML;
        $expected_html = <<<HTML
            <p>&#xC7;a marche&#xA0;?</p>
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    public function testSanitizeWithoutTag(): void
    {
        $html_sanitizer = new HtmlSanitizer();
        $dirty_html = <<<HTML
            Hello
            <div>World!</div>
            HTML;
        $expected_html = <<<HTML
            Hello
            <div>World!</div>
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    public function testSanitizeWithAcceptedLinkScheme(): void
    {
        $html_sanitizer = new HtmlSanitizer([
            'a' => ['href'],
        ]);
        $dirty_html = <<<HTML
            <a href="https://example.org">HTTPS</a>
            <a href="mailto:alice@example.org">Mailto</a>
            <a href="tel:+33612345678">Tel</a>
            <a href="javascript:alert(document.cookie)">JavaScript</a>
            <a href="data:text/html,<script>alert(1)</script>">Data</a>
            HTML;
        $expected_html = <<<HTML
            <a href="https://example.org">HTTPS</a>
            <a href="mailto:alice@example.org">Mailto</a>
            <a href="tel:+33612345678">Tel</a>
            <a href="">JavaScript</a>
            <a href="">Data</a>
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    public function testSanitizeWithAcceptedMediaScheme(): void
    {
        $html_sanitizer = new HtmlSanitizer(
            allowed_elements: [
                'img' => ['src'],
            ],
        );
        $dirty_html = <<<HTML
            <img src="https://example.org">
            <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==">
            <img src="javascript:alert(document.cookie)">
            HTML;
        $expected_html = <<<HTML
            <img src="https://example.org">
            <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==">
            <img src="">
            HTML;

        $healthy_html = $html_sanitizer->sanitize($dirty_html);

        $this->assertSame($expected_html, trim($healthy_html));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('urlsToSanitize')]
    public function testSanitizeUrl(string $dirty_url, string $expected_url): void
    {
        $html_sanitizer = new HtmlSanitizer();

        $sanitized_url = $html_sanitizer->sanitizeUrl($dirty_url, ['http', 'https', 'mailto', 'tel']);

        $this->assertSame($expected_url, $sanitized_url);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function urlsToSanitize(): array
    {
        return [
            ['https://example.org', 'https://example.org'],
            ['mailto:alice@example.org', 'mailto:alice@example.org'],
            ['tel:+33612345678', 'tel:+33612345678'],
            ['/some/path', '/some/path'],
            ['relative/path', 'relative/path'],
            ['../parent/path', '../parent/path'],
            ['#anchor', '#anchor'],
            ['//example.org/path', '//example.org/path'],
            ['javascript:alert(document.cookie)', ''],
            ['data:text/html,<script>alert(1)</script>', ''],
            ["java\tscript:alert(document.cookie)", ''],
            ["\x01javascript:alert(document.cookie)", ''],
        ];
    }
}
