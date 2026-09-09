<?php

namespace App\search_engine\Query;

use App\utils;

/**
 * Transform a query string into a list of tokens.
 *
 * The list is normalized so that the Parser is kept simple: each criteria
 * (text, tag, qualifier or sub-query) is always preceded by an operator (And
 * or Or), the And being inserted implicitly when two criteria follow each
 * other. The list always ends with an EndOfQuery token.
 *
 * The qualifiers depend on the searched data, so their names are given to
 * the Tokenizer as a list.
 *
 * @phpstan-type Token array{
 *     'type': TokenType,
 *     'position': int,
 *     'value'?: non-empty-string,
 *     'quoted'?: bool,
 * }
 *
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Tokenizer
{
    public const KEYWORDS = [
        'AND' => TokenType::And,
        'OR' => TokenType::Or,
        'NOT' => TokenType::Not,
    ];

    /** @var Token[] */
    private array $tokens = [];

    /**
     * @param string[] $qualifiers
     *     The names of the qualifiers accepted in the query.
     * @param bool $tags
     *     Whether the "#tags" are searchable: if not, they are read as text.
     */
    public function __construct(
        private array $qualifiers,
        private bool $tags = true,
    ) {
    }

    /**
     * The text of the token being read.
     */
    private string $currentText = '';

    /**
     * The position of the first char of the token being read. It may differ
     * from the position of the first char of the text (e.g. if the text is
     * quoted or escaped).
     */
    private int $tokenPosition = 0;

    /**
     * Whether the current text contains quoted or escaped chars. In this
     * case, it is never interpreted as a keyword, a tag or a qualifier.
     */
    private bool $literal = false;

    /**
     * Whether the current text contains quoted chars. A quoted text is
     * searched as a phrase.
     */
    private bool $quoted = false;

    /**
     * Note that the tokenizer never fails: a double quote or a bracket which
     * is not closed is considered as closed at the end of the query, and a
     * final backslash is ignored.
     *
     * @return Token[]
     */
    public function tokenize(string $query): array
    {
        $this->tokens = [];
        $this->resetCurrentText();

        // Add a final whitespace to simplify the foreach loop
        $query = $query . ' ';

        $charPosition = 0;
        $quoteOpenPosition = 0;
        /** @var int[] */
        $bracketsOpenPositions = [];
        $escaped = false;

        foreach (mb_str_split($query) as $char) {
            $charPosition += 1;
            $isWhitespace = \ctype_space($char);
            $inQuotes = $quoteOpenPosition > 0;
            $inBrackets = count($bracketsOpenPositions) > 0;

            if ($this->tokenPosition === 0 && !$isWhitespace) {
                $this->tokenPosition = $charPosition;
            }

            if ($escaped) {
                // The current char is escaped, so we add it to the current
                // text, even if it's a special char (e.g. whitespace, quote,
                // etc.)
                $this->currentText .= $char;
                $escaped = false;
            } elseif ($char === '\\') {
                // The current char is a (not escaped) backslash, so we set
                // the variable $escaped to true to escape the next char.
                $escaped = true;
                $this->literal = true;
            } elseif ($char === '"') {
                // The current char is a quote, so we change the quoteOpenPosition
                // depending on the fact we're already in quotes or not.
                $quoteOpenPosition = $inQuotes ? 0 : $charPosition;
                $this->literal = true;
                $this->quoted = true;
            } elseif ($inQuotes) {
                // The current char is in quotes, so we add it to the current
                // text, even if it's a special char.
                $this->currentText .= $char;
            } elseif ($char === '(' && $this->currentText === '' && !$this->literal) {
                // A bracket opens a sub-query only at the start of a token,
                // so that the brackets inside a text (e.g. a URL) are kept
                // as is.
                $bracketsOpenPositions[] = $charPosition;

                $this->addToken([
                    'type' => TokenType::OpenBracket,
                    'position' => $charPosition,
                ]);

                $this->resetCurrentText();
            } elseif ($char === ')' && $inBrackets) {
                // A bracket closes a sub-query only if one is open, so that
                // the other brackets are kept as is.
                $this->addCurrentTextTokens();

                array_pop($bracketsOpenPositions);

                $this->addToken([
                    'type' => TokenType::CloseBracket,
                    'position' => $charPosition,
                ]);
            } elseif ($char === ':' && !$this->literal && $this->isQualifier($this->currentText)) {
                // The current text is a qualifier (possibly negated with a
                // leading "-").
                $this->addQualifierTokens($this->currentText, $this->tokenPosition);

                $this->resetCurrentText();
            } elseif (!$isWhitespace) {
                // We are at the end of the possibilities. We just check that
                // the current char is not a whitespace, and we add it to the
                // currentText.
                $this->currentText .= $char;
            } else {
                // The current char is a whitespace, so the token is complete.
                $this->addCurrentTextTokens();
            }
        }

        if ($this->currentText !== '') {
            // The whitespace added above has been read as text: a quote is
            // not closed, or the query ends with a backslash. The text is
            // read as if the quote was closed (or the backslash ignored) at
            // the end of the query, without this whitespace.
            $this->currentText = mb_substr($this->currentText, 0, -1);
            $this->addCurrentTextTokens();
        }

        foreach ($bracketsOpenPositions as $bracketOpenPosition) {
            // The bracket is not closed: it is closed at the end of the query.
            $this->addToken([
                'type' => TokenType::CloseBracket,
                'position' => $charPosition,
            ]);
        }

        $this->tokens[] = [
            'type' => TokenType::EndOfQuery,
            'position' => $charPosition,
        ];

        return $this->tokens;
    }

    private function resetCurrentText(): void
    {
        $this->currentText = '';
        $this->tokenPosition = 0;
        $this->literal = false;
        $this->quoted = false;
    }

    /**
     * Add the tokens corresponding to the current text (if any) to the list,
     * and reset it.
     */
    private function addCurrentTextTokens(): void
    {
        $text = $this->currentText;

        if ($text === '') {
            // There is no text (e.g. after a "()" or a ""), but the position
            // and the flags may have been set: they must not leak into the
            // next token.
            $this->resetCurrentText();

            return;
        }

        if (!$this->literal && isset(self::KEYWORDS[$text])) {
            $this->addKeywordToken($text, $this->tokenPosition);
        } elseif (!$this->literal && $this->tags && $this->isTag($text)) {
            $this->addTagTokens($text, $this->tokenPosition);
        } else {
            $this->addTextToken($text, $this->tokenPosition, $this->quoted);
        }

        $this->resetCurrentText();
    }

    /**
     * Return whether the text is one of the qualifiers, possibly negated with
     * a leading "-".
     */
    private function isQualifier(string $text): bool
    {
        if (str_starts_with($text, '-')) {
            $text = mb_substr($text, 1);
        }

        return in_array($text, $this->qualifiers);
    }

    private function addQualifierTokens(string $text, int $position): void
    {
        if (str_starts_with($text, '-')) {
            // The qualifier starts with a "-", so we transform this char into
            // a "Not" token.
            $this->addToken([
                'type' => TokenType::Not,
                'position' => $position,
            ]);

            $text = mb_substr($text, 1);
            $position += 1;
        }

        assert($text !== '');

        $this->addToken([
            'type' => TokenType::Qualifier,
            'value' => $text,
            'position' => $position,
        ]);
    }

    /**
     * @param key-of<self::KEYWORDS> $keyword
     */
    private function addKeywordToken(string $keyword, int $position): void
    {
        $this->addToken([
            'type' => self::KEYWORDS[$keyword],
            'position' => $position,
        ]);
    }

    /**
     * Return whether the text is a tag, possibly negated with a leading "-".
     */
    private function isTag(string $text): bool
    {
        if (str_starts_with($text, '-')) {
            $text = mb_substr($text, 1);
        }

        return utils\Tag::isValid($text);
    }

    /**
     * @param non-empty-string $text
     */
    private function addTagTokens(string $text, int $position): void
    {
        if ($text[0] === '-') {
            // The tag starts with a "-", so we transform this char into a
            // "Not" token.
            $this->addToken([
                'type' => TokenType::Not,
                'position' => $position,
            ]);

            $text = mb_substr($text, 1);
            $position += 1;
        }

        // Remove the "#" char
        $text = mb_substr($text, 1);

        assert($text !== '');

        $this->addToken([
            'type' => TokenType::Tag,
            'value' => $text,
            'position' => $position,
        ]);
    }

    /**
     * @param non-empty-string $text
     */
    private function addTextToken(string $text, int $position, bool $quoted): void
    {
        $this->addToken([
            'type' => TokenType::Text,
            'value' => $text,
            'position' => $position,
            'quoted' => $quoted,
        ]);
    }

    /**
     * Add a token to the list, inserting an implicit And before it if needed.
     *
     * @param Token $token
     */
    private function addToken(array $token): void
    {
        if ($this->isImplicitAndNeeded($token)) {
            $this->tokens[] = [
                'type' => TokenType::And,
                'position' => $token['position'],
            ];
        }

        $this->tokens[] = $token;
    }

    /**
     * Return whether an implicit And must be inserted before the given token.
     *
     * The Parser expects each condition to be preceded by an And or an Or,
     * while the user writes "some text" for "some AND text". So an And must
     * be inserted before a token starting a condition (Text, Tag, Qualifier,
     * OpenBracket or Not), unless the previous token makes it useless:
     *
     * - an And or an Or, when the user wrote the operator explicitly;
     * - a Not, as the condition is already started by the negation;
     * - a Qualifier, as the token is its value, not a new condition.
     *
     * A condition at the very start of the list needs an And too, and so
     * does the first condition of a sub-query (i.e. after an OpenBracket):
     * the Parser reads a sub-query like the main one.
     *
     * @param Token $token
     */
    private function isImplicitAndNeeded(array $token): bool
    {
        $conditionStarters = [
            TokenType::Text,
            TokenType::Tag,
            TokenType::Qualifier,
            TokenType::OpenBracket,
            TokenType::Not,
        ];

        if (!in_array($token['type'], $conditionStarters)) {
            return false;
        }

        $previousToken = end($this->tokens);

        if ($previousToken === false) {
            return true;
        }

        $tokensMakingAndUseless = [
            TokenType::And,
            TokenType::Or,
            TokenType::Not,
            TokenType::Qualifier,
        ];

        return !in_array($previousToken['type'], $tokensMakingAndUseless);
    }
}
