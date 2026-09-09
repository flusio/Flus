<?php

namespace App\search_engine\Query;

/**
 * @phpstan-import-type Token from Tokenizer
 */
class TokenizerTest extends \PHPUnit\Framework\TestCase
{
    public const QUALIFIERS = ['url'];

    /**
     * @param Token[] $expectedTokens
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tokensProvider')]
    public function testTokenize(string $query, array $expectedTokens): void
    {
        $tokenizer = new Tokenizer(self::QUALIFIERS);
        // EndOfQuery must be present at the end of all the list of tokens.
        // This allows to not clutter the provider with a token that is always
        // present.
        $expectedTokens[] = [
            'type' => TokenType::EndOfQuery,
            'position' => mb_strlen($query) + 1,
        ];

        $tokens = $tokenizer->tokenize($query);

        $this->assertSame(count($expectedTokens), count($tokens));
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $expectedToken = $expectedTokens[$i];
            $this->assertSame($expectedToken['type'], $token['type']);
            $this->assertSame($expectedToken['position'], $token['position']);
            if (isset($expectedToken['value'])) {
                $this->assertTrue(isset($token['value']));
                $this->assertSame($expectedToken['value'], $token['value']);
            }
            if (isset($expectedToken['quoted'])) {
                $this->assertTrue(isset($token['quoted']));
                $this->assertSame($expectedToken['quoted'], $token['quoted']);
            }
        }
    }

    /**
     * Note that a criteria is always preceded by an operator: the "And" is
     * inserted by the tokenizer when it is implicit.
     *
     * @return array<array{string, Token[]}>
     */
    public static function tokensProvider(): array
    {
        return [
            [
                'some text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 6],
                ],
            ],

            [
                '"some text"',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some text', 'position' => 1, 'quoted' => true],
                ],
            ],

            [
                'some\ text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some text', 'position' => 1, 'quoted' => false],
                ],
            ],

            [
                '(some OR text) more',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::OpenBracket, 'position' => 1],
                    ['type' => TokenType::And, 'position' => 2],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 2],
                    ['type' => TokenType::Or, 'position' => 7],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 10],
                    ['type' => TokenType::CloseBracket, 'position' => 14],
                    ['type' => TokenType::And, 'position' => 16],
                    ['type' => TokenType::Text, 'value' => 'more', 'position' => 16],
                ],
            ],

            [
                'NOT (some (text))',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Not, 'position' => 1],
                    ['type' => TokenType::OpenBracket, 'position' => 5],
                    ['type' => TokenType::And, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 6],
                    ['type' => TokenType::And, 'position' => 11],
                    ['type' => TokenType::OpenBracket, 'position' => 11],
                    ['type' => TokenType::And, 'position' => 12],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 12],
                    ['type' => TokenType::CloseBracket, 'position' => 16],
                    ['type' => TokenType::CloseBracket, 'position' => 17],
                ],
            ],

            [
                'url:wiki/Foo_(bar) text)',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'wiki/Foo_(bar)', 'position' => 5],
                    ['type' => TokenType::And, 'position' => 20],
                    ['type' => TokenType::Text, 'value' => 'text)', 'position' => 20],
                ],
            ],

            [
                '"(some text)"',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => '(some text)', 'position' => 1],
                ],
            ],

            [
                '\"some text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => '"some', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 8],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 8],
                ],
            ],

            [
                '\some text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 7],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 7],
                ],
            ],

            [
                '\\\\', // equivalent to '\\' in the string passed to the tokenizer
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => '\\', 'position' => 1],
                ],
            ],

            [
                'some AND text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 10],
                ],
            ],

            [
                'some OR text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 1],
                    ['type' => TokenType::Or, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 9],
                ],
            ],

            [
                'some NOT text',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 6],
                    ['type' => TokenType::Not, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 10],
                ],
            ],

            [
                '"OR" or Or',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'OR', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'or', 'position' => 6],
                    ['type' => TokenType::And, 'position' => 9],
                    ['type' => TokenType::Text, 'value' => 'Or', 'position' => 9],
                ],
            ],

            [
                'url: https://flus.fr',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'https://flus.fr', 'position' => 6],
                ],
            ],

            [
                'url:',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 1],
                ],
            ],

            [
                '-url:flus.fr',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Not, 'position' => 1],
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 2],
                    ['type' => TokenType::Text, 'value' => 'flus.fr', 'position' => 6],
                ],
            ],

            [
                '"url: https://flus.fr"',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'url: https://flus.fr', 'position' => 1],
                ],
            ],

            [
                'https://flus.fr',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'https://flus.fr', 'position' => 1],
                ],
            ],

            [
                '#tag',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 1],
                ],
            ],

            [
                '-#tag',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Not, 'position' => 1],
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 2],
                ],
            ],

            [
                '"#tag"',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => '#tag', 'position' => 1],
                ],
            ],

            [
                '#tag-name',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => '#tag-name', 'position' => 1],
                ],
            ],

            [
                'some text #tag url: https://flus.fr "and more text"',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 1],
                    ['type' => TokenType::And, 'position' => 6],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 6],
                    ['type' => TokenType::And, 'position' => 11],
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 11],
                    ['type' => TokenType::And, 'position' => 16],
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 16],
                    ['type' => TokenType::Text, 'value' => 'https://flus.fr', 'position' => 21],
                    ['type' => TokenType::And, 'position' => 37],
                    ['type' => TokenType::Text, 'value' => 'and more text', 'position' => 37],
                ],
            ],
            [
                // The empty quotes must not make the tag literal.
                '"" #tag',
                [
                    ['type' => TokenType::And, 'position' => 4],
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 4],
                ],
            ],
            [
                // The empty brackets must not shift the position of the text.
                '() some',
                [
                    ['type' => TokenType::And, 'position' => 1],
                    ['type' => TokenType::OpenBracket, 'position' => 1],
                    ['type' => TokenType::CloseBracket, 'position' => 2],
                    ['type' => TokenType::And, 'position' => 4],
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 4],
                ],
            ],
        ];
    }

    public function testTokenizeClosesQuoteAtTheEnd(): void
    {
        $tokenizer = new Tokenizer(self::QUALIFIERS);

        $tokens = $tokenizer->tokenize('some "text more');

        $this->assertSame(5, count($tokens));
        $this->assertSame(TokenType::Text, $tokens[3]['type']);
        $this->assertSame('text more', $tokens[3]['value'] ?? null);
        $this->assertTrue($tokens[3]['quoted'] ?? false);
        $this->assertSame(TokenType::EndOfQuery, $tokens[4]['type']);
    }

    public function testTokenizeIgnoresBackslashAtTheEnd(): void
    {
        $tokenizer = new Tokenizer(self::QUALIFIERS);

        $tokens = $tokenizer->tokenize('some text\\');

        $this->assertSame(5, count($tokens));
        $this->assertSame(TokenType::Text, $tokens[3]['type']);
        $this->assertSame('text', $tokens[3]['value'] ?? null);
        $this->assertSame(TokenType::EndOfQuery, $tokens[4]['type']);
    }

    public function testTokenizeReadsTagsAsTextIfTagsAreDisabled(): void
    {
        $tokenizer = new Tokenizer(self::QUALIFIERS, tags: false);

        $tokens = $tokenizer->tokenize('#tag -#tag');

        $this->assertSame(TokenType::Text, $tokens[1]['type']);
        $this->assertSame('#tag', $tokens[1]['value'] ?? null);
        $this->assertSame(TokenType::Text, $tokens[3]['type']);
        $this->assertSame('-#tag', $tokens[3]['value'] ?? null);
    }

    public function testTokenizeClosesBracketsAtTheEnd(): void
    {
        $tokenizer = new Tokenizer(self::QUALIFIERS);

        $tokens = $tokenizer->tokenize('some (text (more');

        $this->assertSame(13, count($tokens));
        $this->assertSame(TokenType::Text, $tokens[9]['type']);
        $this->assertSame(TokenType::CloseBracket, $tokens[10]['type']);
        $this->assertSame(TokenType::CloseBracket, $tokens[11]['type']);
        $this->assertSame(TokenType::EndOfQuery, $tokens[12]['type']);
    }
}
