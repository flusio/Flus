<?php

namespace App\utils;

class TagTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string[] $expected_tags
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tags')]
    public function testExtract(string $content, array $expected_tags): void
    {
        $tags = Tag::extract($content);

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

    #[\PHPUnit\Framework\Attributes\DataProvider('validTags')]
    public function testIsValid(string $tag, bool $expected_valid): void
    {
        $valid = Tag::isValid($tag);

        $this->assertSame($expected_valid, $valid);
    }

    /**
     * @return array<array{string, bool}>
     */
    public static function validTags(): array
    {
        return [
            ['#foo', true],
            ['#123', true],
            ['#féè', true],
            ['#foo_', true],

            ['foo', false],
            ['#', false],
            ['#foo-bar', false],
            ['#foo bar', false],
            ['#foo🤖', false],
            ['-#foo', false],
        ];
    }
}
