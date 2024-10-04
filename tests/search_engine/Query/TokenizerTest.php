<?php

namespace App\search_engine\Query;

/**
 * @phpstan-import-type Token from Tokenizer
 */
class TokenizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param Token[] $expectedTokens
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tokensProvider')]
    public function testTokenize(string $query, array $expectedTokens): void
    {
        $tokenizer = new Tokenizer();
        // EndOfQuery must be present at the end of all the list of tokens.
        // This allows to not clutter the provider with a token that is always
        // present.
        $expectedTokens[] = ['type' => TokenType::EndOfQuery];

        $tokens = $tokenizer->tokenize($query);

        $this->assertSame(count($expectedTokens), count($tokens));
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $expectedToken = $expectedTokens[$i];
            $this->assertSame($expectedToken['type'], $token['type']);
            if (isset($expectedToken['value'])) {
                $this->assertTrue(isset($token['value']));
                $this->assertSame($expectedToken['value'], $token['value']);
            }
        }
    }

    /**
     * @return array<array{string, Token[]}>
     */
    public static function tokensProvider(): array
    {
        // Note that positions are wrong in the Tokens. This is because it
        // would be too complicated to maintain correctly and efficiently.
        // Also, they are not used during tests. It's mainly so that PHPStan
        // doesn't scream too loud.
        return [
            [
                'some text',
                [
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 0],
                ],
            ],

            [
                '"some text"',
                [
                    ['type' => TokenType::Text, 'value' => 'some text', 'position' => 0],
                ],
            ],

            [
                '\"some text',
                [
                    ['type' => TokenType::Text, 'value' => '"some', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 0],
                ],
            ],

            [
                '\some text',
                [
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 0],
                ],
            ],

            [
                'some\ text',
                [
                    ['type' => TokenType::Text, 'value' => 'some text', 'position' => 0],
                ],
            ],

            [
                '\\\\', // equivalent to '\\' in the string passed to the tokenizer
                [
                    ['type' => TokenType::Text, 'value' => '\\', 'position' => 0],
                ],
            ],

            [
                '"some text',
                [
                    ['type' => TokenType::Text, 'value' => 'some text ', 'position' => 0],
                ],
            ],

            [
                'url: https://flus.fr',
                [
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'https://flus.fr', 'position' => 0],
                ],
            ],

            [
                'url:',
                [
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 0],
                ],
            ],

            [
                '"url: https://flus.fr"',
                [
                    ['type' => TokenType::Text, 'value' => 'url: https://flus.fr', 'position' => 0],
                ],
            ],

            [
                '#tag',
                [
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 0],
                ],
            ],

            [
                '-#tag',
                [
                    ['type' => TokenType::Not, 'position' => 0],
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 0],
                ],
            ],

            [
                'some text #tag url: https://flus.fr "and more text"',
                [
                    ['type' => TokenType::Text, 'value' => 'some', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'text', 'position' => 0],
                    ['type' => TokenType::Tag, 'value' => 'tag', 'position' => 0],
                    ['type' => TokenType::Qualifier, 'value' => 'url', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'https://flus.fr', 'position' => 0],
                    ['type' => TokenType::Text, 'value' => 'and more text', 'position' => 0],
                ],
            ],
        ];
    }
}
