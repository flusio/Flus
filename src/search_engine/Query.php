<?php

namespace App\search_engine;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Query
{
    /** @var Query\Condition[] */
    private array $conditions = [];

    public function addCondition(Query\Condition $condition): void
    {
        $this->conditions[] = $condition;
    }

    /**
     * @param 'text'|'qualifier'|'tag'|'any' $type
     *
     * @return Query\Condition[]
     */
    public function getConditions(string $type = 'any'): array
    {
        if ($type === 'any') {
            return $this->conditions;
        }

        return array_filter($this->conditions, function ($condition) use ($type) {
            if ($type === 'text') {
                return $condition->isTextCondition();
            } elseif ($type === 'qualifier') {
                return $condition->isQualifierCondition();
            } elseif ($type === 'tag') {
                return $condition->isTagCondition();
            }
        });
    }

    public static function fromString(string $queryString): Query
    {
        $tokenizer = new Query\Tokenizer();
        $parser = new Query\Parser();
        $tokens = $tokenizer->tokenize($queryString);
        return $parser->parse($tokens);
    }
}
