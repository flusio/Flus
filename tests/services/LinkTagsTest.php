<?php

namespace App\services;

class LinkTagsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string[] $expected_tags
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tags')]
    public function testExtractTags(string $content, array $expected_tags): void
    {
        $tags = LinkTags::extractTags($content);

        $this->assertSame(count($expected_tags), count($tags));
        foreach ($tags as $tag) {
            $this->assertContains($tag, $expected_tags);
        }
    }

    /**
     * @return array<array{string, string[]}>
     */
    public static function tags(): array
    {
        return [
            ['#foo', ['foo']],
            ['#foo #bar', ['foo', 'bar']],
            ['#123', ['123']],
            ['#féè', ['féè']],
            ['#foo_', ['foo_']],
            ['#foo🤖', ['foo']],
            ['"#foo!', ['foo']],
            ['-#foo.', ['foo']],
            ['##foo?', ['foo']],
            ['^#foo,', ['foo']],

            ['a#foo', []],
            ['_#foo', []],
            ['#', []],
        ];
    }
}
