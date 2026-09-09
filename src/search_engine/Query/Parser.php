<?php

namespace App\search_engine\Query;

use App\search_engine\Query;
use App\search_engine\SyntaxError;

/**
 * The LL grammar is defined by the following rules:
 *
 * S -> QUERY
 *
 * QUERY -> CONDITIONAL_QUERY QUERY
 * QUERY -> CONDITIONAL_QUERY end_of_query
 *
 * CONDITIONAL_QUERY -> and CONDITION
 * CONDITIONAL_QUERY -> or CONDITION
 *
 * CONDITION -> CRITERIA
 * CONDITION -> not CONDITION
 *
 * CRITERIA -> text
 * CRITERIA -> tag
 * CRITERIA -> qualifier text
 * CRITERIA -> open_bracket QUERY close_bracket
 *
 * Each rule of the grammar is implemented by a method in the Parser class to
 * make the code as clear as possible.
 *
 * Note that the Tokenizer guarantees that each criteria is preceded by an
 * operator (see Tokenizer::addToken()).
 *
 * The parser is lenient: what cannot be interpreted is ignored rather than
 * rejected (an operator repeated or at the end of the query, a qualifier
 * without value, an empty sub-query). The only syntax error is a qualifier
 * used with a value it doesn't accept.
 *
 * The qualifiers depend on the searched data, so they are given to the
 * Parser, mapped to the values they accept: "@text" for any value, a list of
 * values, or a regex.
 *
 * @phpstan-import-type Token from Tokenizer
 * @phpstan-type Qualifiers array<string, '@text'|string[]|non-empty-string>
 *
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Parser
{
    /** @var Token[] */
    private array $tokens;

    /**
     * @param Qualifiers $qualifiers
     *     The qualifiers accepted in the query, mapped to the values they
     *     accept.
     */
    public function __construct(
        private array $qualifiers,
    ) {
    }

    /**
     * @throws SyntaxError
     *     Raised if a qualifier is used with a value it doesn't accept.
     * @throws \LogicException
     *     Raised if the list of tokens is empty or invalid.
     *
     * @param Token[] $tokens
     */
    public function parse(array $tokens): Query
    {
        if (empty($tokens)) {
            throw new \LogicException('The parser cannot be called with an empty list of tokens.');
        }

        $this->tokens = $tokens;

        $query = new Query();
        $this->ruleQuery($query);
        $this->consumeToken(TokenType::EndOfQuery);
        return $query;
    }

    private function ruleQuery(Query $query): void
    {
        $this->ruleConditionalQuery($query);

        $currentToken = $this->readToken();

        if (
            $currentToken['type'] === TokenType::And ||
            $currentToken['type'] === TokenType::Or
        ) {
            $this->ruleQuery($query);
        }

        // Otherwise, the query is complete: the caller consumes the token
        // ending it (EndOfQuery or CloseBracket).
    }

    private function ruleConditionalQuery(Query $query): void
    {
        // The operator is the last one of the sequence, the others are
        // ignored (e.g. "some AND OR text").
        $operator = 'and';

        $currentToken = $this->readToken();

        while (
            $currentToken['type'] === TokenType::And ||
            $currentToken['type'] === TokenType::Or
        ) {
            $operator = $currentToken['type'] === TokenType::Or ? 'or' : 'and';

            $this->consumeToken($currentToken['type']);

            $currentToken = $this->readToken();
        }

        $this->ruleCondition($query, $operator);
    }

    /**
     * @param value-of<Condition::OPERATORS> $operator
     */
    private function ruleCondition(Query $query, string $operator): void
    {
        $not = false;

        // The negations cancel each other (e.g. "NOT NOT some" is "some").
        $currentToken = $this->readToken();

        while ($currentToken['type'] === TokenType::Not) {
            $this->consumeToken(TokenType::Not);

            $not = !$not;

            $currentToken = $this->readToken();
        }

        $this->ruleCriteria($query, $operator, $not);
    }

    /**
     * @param value-of<Condition::OPERATORS> $operator
     */
    private function ruleCriteria(Query $query, string $operator, bool $not): void
    {
        $currentToken = $this->readToken();

        if ($currentToken['type'] === TokenType::Text) {
            $this->consumeToken(TokenType::Text);

            $value = $currentToken['value'] ?? '';
            $quoted = $currentToken['quoted'] ?? false;

            $condition = Condition::textCondition($operator, $value, $not, phrase: $quoted);
        } elseif ($currentToken['type'] === TokenType::Tag) {
            $this->consumeToken(TokenType::Tag);

            $tag = $currentToken['value'] ?? '';

            $condition = Condition::tagCondition($operator, $tag, $not);
        } elseif ($currentToken['type'] === TokenType::Qualifier) {
            $this->consumeToken(TokenType::Qualifier);

            $qualifier = $currentToken['value'] ?? '';

            $valueToken = $this->readToken();

            if ($valueToken['type'] !== TokenType::Text) {
                // The qualifier has no value: it is ignored with its operator
                // and its negation, and the next token is read as a new
                // condition (e.g. "OR url: #tag" gives "AND #tag").
                $this->ruleCondition($query, 'and');

                return;
            }

            $this->consumeToken(TokenType::Text);

            $value = $valueToken['value'] ?? '';

            $validValues = $this->qualifiers[$qualifier] ?? '@text';

            if (
                (is_array($validValues) && !in_array($value, $validValues)) ||
                (is_string($validValues) && $validValues !== '@text' && preg_match($validValues, $value) !== 1)
            ) {
                throw SyntaxError::qualifierValueInvalid($valueToken['position'], $qualifier, $value);
            }

            $quoted = $valueToken['quoted'] ?? false;

            $condition = Condition::qualifierCondition($operator, $qualifier, $value, $not, phrase: $quoted);
        } elseif ($currentToken['type'] === TokenType::OpenBracket) {
            $this->consumeToken(TokenType::OpenBracket);

            $subQuery = new Query();
            $this->ruleQuery($subQuery);

            $this->consumeToken(TokenType::CloseBracket);

            if ($subQuery->isEmpty()) {
                // The sub-query is empty (e.g. "()" or "(OR)"): it is ignored.
                return;
            }

            $condition = Condition::queryCondition($operator, $subQuery, $not);
        } else {
            // The token cannot start a criteria (e.g. the end of the query
            // after an operator): there is no condition.
            return;
        }

        $query->addCondition($condition);
    }

    /**
     * @return Token
     */
    private function readToken(): array
    {
        $token = reset($this->tokens);

        if ($token === false) {
            throw new \LogicException('The parser expected a token to be present but the list is empty.');
        }

        return $token;
    }

    private function consumeToken(TokenType $expectedTokenType): void
    {
        $currentToken = $this->readToken();

        if ($currentToken['type'] !== $expectedTokenType) {
            // The rules only consume the tokens they have read, and the
            // Tokenizer guarantees that the brackets and the query are
            // closed, so this should never happen.
            $type = $currentToken['type']->value;
            $pos = $currentToken['position'];

            throw new \LogicException("Unexpected token {$type} at position {$pos}");
        }

        array_shift($this->tokens);
    }
}
