<?php

namespace App\search_engine\Query;

use App\search_engine\Query;

/**
 * The LL grammar is defined by the following rules:
 *
 * S -> QUERY
 *
 * QUERY -> CRITERIA QUERY
 * QUERY -> CRITERIA end_of_query
 *
 * CRITERIA -> text
 * CRITERIA -> qualifier_url
 * CRITERIA -> qualifier_url text
 * CRITERIA -> tag
 * CRITERIA -> not tag
 *
 * Each rule of the grammar is implemented by a method in the Parser class to
 * make the code as clear as possible.
 *
 * @phpstan-import-type Token from Tokenizer
 *
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Parser
{
    /** @var Token[] */
    private array $tokens;

    /** @param Token[] $tokens */
    public function parse(array $tokens): Query
    {
        if (empty($tokens)) {
            throw new \LogicException('The parser cannot be called with an empty list of tokens.');
        }

        $this->tokens = $tokens;

        $query = new Query();
        $this->ruleQuery($query);
        return $query;
    }

    private function ruleQuery(Query $query): void
    {
        $this->ruleCriteria($query);

        $currentToken = $this->readToken();

        if ($currentToken['type'] === TokenType::EndOfQuery) {
            $this->consumeToken(TokenType::EndOfQuery);
        } else {
            $this->ruleQuery($query);
        }
    }

    private function ruleCriteria(Query $query): void
    {
        $currentToken = $this->readToken();

        if ($currentToken['type'] === TokenType::Text) {
            $this->consumeToken(TokenType::Text);

            $value = $currentToken['value'] ?? '';

            $condition = Query\Condition::textCondition($value);
        } elseif ($currentToken['type'] === TokenType::Qualifier) {
            $this->consumeToken(TokenType::Qualifier);

            $qualifier = $currentToken['value'] ?? '';

            $currentToken = $this->readToken();

            if ($currentToken['type'] === TokenType::Text) {
                $this->consumeToken(TokenType::Text);

                $value = $currentToken['value'] ?? '';

                $condition = Query\Condition::qualifierCondition($qualifier, $value);
            } else {
                $condition = Query\Condition::textCondition($qualifier);
            }
        } elseif ($currentToken['type'] === TokenType::Tag) {
            $this->consumeToken(TokenType::Tag);

            $tag = $currentToken['value'] ?? '';

            $condition = Query\Condition::tagCondition($tag, not: false);
        } elseif ($currentToken['type'] === TokenType::Not) {
            $this->consumeToken(TokenType::Not);

            $currentToken = $this->readToken();

            $this->consumeToken(TokenType::Tag);

            $tag = $currentToken['value'] ?? '';

            $condition = Query\Condition::tagCondition($tag, not: true);
        } else {
            $type = $currentToken['type']->value;
            $pos = $currentToken['position'];

            throw new \LogicException("Unexpected token {$type} at position {$pos}");
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
            $type = $currentToken['type']->value;
            $pos = $currentToken['position'];

            throw new \LogicException("Unexpected token {$type} at position {$pos}");
        }

        array_shift($this->tokens);
    }
}
