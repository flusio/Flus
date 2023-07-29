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
}
