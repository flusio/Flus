<?php

namespace App\search_engine\Query;

/**
 * @phpstan-type Token array{
 *     'type': TokenType,
 *     'position': int,
 *     'value'?: non-empty-string,
 * }
 *
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Tokenizer
{
    public const VALID_QUALIFIERS = ['url'];

    /**
     * @return Token[]
     */
    public function tokenize(string $query): array
    {
        $tokens = [];

        // Add a final whitespace to simplify the foreach loop
        $query = $query . ' ';

        $charPosition = 0;
        $currentText = '';
        $quoteOpenPosition = 0;
        $escaped = false;

        foreach (mb_str_split($query) as $char) {
            $charPosition += 1;
            $isWhitespace = \ctype_space($char);
            $inQuotes = $quoteOpenPosition > 0;

            if ($escaped) {
                // The current char is escaped, so we add it to the current
                // text, even if it's a special char (e.g. whitespace, comma,
                // quote, etc.)
                $currentText = $currentText . $char;
                $escaped = false;
            } elseif ($char === '\\') {
                // The current char is a (not escaped) backslash, so we set
                // the variable $escaped to true to escape the next char.
                $escaped = true;
            } elseif ($char === '"') {
                // The current char is a quote, so we change the quoteOpenPosition
                // depending on the fact we're already in quotes or not.
                $quoteOpenPosition = $inQuotes ? 0 : $charPosition;
            } elseif ($inQuotes) {
                // The current char is in quotes, so we add it to the current
                // text, even if it's a special char.
                $currentText = $currentText . $char;
            } elseif (
                $char === ':' &&
                $currentText !== '' &&
                in_array($currentText, self::VALID_QUALIFIERS)
            ) {
                $tokens[] = [
                    'type' => TokenType::Qualifier,
                    'value' => $currentText,
                    'position' => $charPosition - mb_strlen($currentText),
                ];

                $currentText = '';
            } elseif (!$isWhitespace) {
                // We are at the end of the possibilities. We just check that
                // the current char is not a whitespace, and we add it to the
                // currentText.
                $currentText = $currentText . $char;
            } elseif ($isWhitespace && $currentText !== '') {
                // If the current char is a whitespace, the token is complete.
                // We add it to the list of tokens.
                $tokens = array_merge(
                    $tokens,
                    $this->textToTokens($currentText, $charPosition)
                );
                $currentText = '';
            }
        }

        if ($quoteOpenPosition > 0 && $currentText !== '') {
            $tokens = array_merge(
                $tokens,
                $this->textToTokens($currentText, $charPosition)
            );
            $currentText = '';
        }

        $tokens[] = [
            'type' => TokenType::EndOfQuery,
            'position' => $charPosition + 1,
        ];

        return $tokens;
    }

    /**
     * @param non-empty-string $text
     *
     * @return Token[]
     */
    private function textToTokens(string $text, int $positionEnd): array
    {
        $position = $positionEnd - mb_strlen($text);

        $tag_regex = '/^-?#[\pL\pN_]+/u';
        if (preg_match($tag_regex, $text) === 1) {
            $tokens = [];

            if ($text[0] === '-') {
                // If the qualifier starts with a "-", we transform this char
                // into a "Not" token.
                $tokens[] = [
                    'type' => TokenType::Not,
                    'position' => $position,
                ];

                $text = mb_substr($text, 1);
                $position += 1;
            }

            // Remove the "#" char
            $text = mb_substr($text, 1);

            assert($text !== '');

            $tokens[] = [
                'type' => TokenType::Tag,
                'value' => $text,
                'position' => $position,
            ];

            return $tokens;
        } else {
            return [
                [
                    'type' => TokenType::Text,
                    'value' => $text,
                    'position' => $position,
                ],
            ];
        }
    }
}
