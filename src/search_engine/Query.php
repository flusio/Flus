<?php

namespace App\search_engine;

/**
 * @phpstan-import-type Qualifiers from Query\Parser
 *
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
     * @return Query\Condition[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Return whether the query has no condition (i.e. it filters nothing).
     */
    public function isEmpty(): bool
    {
        return $this->conditions === [];
    }

    /**
     * Return a Query from the given string.
     *
     * The syntax is lenient (see Query\Parser): a string without any
     * condition (e.g. an empty string) gives an empty Query.
     *
     * @throws SyntaxError
     *     Raised if a qualifier is used with a value it doesn't accept.
     *
     * @param Qualifiers $qualifiers
     *     The qualifiers accepted in the query, mapped to the values they
     *     accept (see Query\Parser).
     * @param bool $tags
     *     Whether the "#tags" are searchable (see Query\Tokenizer).
     */
    public static function fromString(string $queryString, array $qualifiers, bool $tags = true): Query
    {
        $tokenizer = new Query\Tokenizer(array_keys($qualifiers), $tags);
        $parser = new Query\Parser($qualifiers);
        $tokens = $tokenizer->tokenize($queryString);
        return $parser->parse($tokens);
    }
}
