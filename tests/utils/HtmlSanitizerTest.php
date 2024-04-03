<?php

namespace App\utils;

class HtmlSanitizerTest extends \PHPUnit\Framework\TestCase
{
    public function testSanitizeCollectionDescription(): void
    {
        $description = <<<HTML
            <h1>Welcome!</h1>
            <div>Hello <a href="/world">World</a></div>
            <script>alert('oops');</script>
            HTML;
        $expected_description = <<<HTML
            <div>Welcome!
            <div>Hello <a href="https://example.com/world" target="_blank" rel="noopener noreferrer">World</a></div>

            </div>
            HTML;
        $base_url = 'https://example.com';

        $result = HtmlSanitizer::sanitizeCollectionDescription($description, $base_url);

        $this->assertSame($expected_description, trim($result));
    }
}
