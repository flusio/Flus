<?php

namespace App\search_engine\Query;

use App\search_engine\Query;

/**
 * A condition of a Query.
 *
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Condition
{
    public const TYPES = ['text', 'qualifier', 'tag', 'query'];
    public const OPERATORS = ['and', 'or'];

    /**
     * @param value-of<self::TYPES> $type
     * @param value-of<self::OPERATORS> $operator
     */
    private function __construct(
        /** @var value-of<self::TYPES> */
        private string $type,
        /** @var value-of<self::OPERATORS> */
        private string $operator,
        private string $value,
        private ?string $qualifier,
        private ?Query $query,
        private bool $not,
        private bool $phrase,
    ) {
    }

    /**
     * @param value-of<self::OPERATORS> $operator
     */
    public static function textCondition(string $operator, string $value, bool $not, bool $phrase = false): self
    {
        return new self('text', $operator, $value, null, null, $not, $phrase);
    }

    /**
     * @param value-of<self::OPERATORS> $operator
     */
    public static function qualifierCondition(
        string $operator,
        string $qualifier,
        string $value,
        bool $not,
        bool $phrase = false,
    ): self {
        return new self('qualifier', $operator, $value, $qualifier, null, $not, $phrase);
    }

    /**
     * @param value-of<self::OPERATORS> $operator
     */
    public static function tagCondition(string $operator, string $value, bool $not): self
    {
        return new self('tag', $operator, $value, null, null, $not, false);
    }

    /**
     * @param value-of<self::OPERATORS> $operator
     */
    public static function queryCondition(string $operator, Query $query, bool $not): self
    {
        return new self('query', $operator, '', null, $query, $not, false);
    }

    public function isTextCondition(): bool
    {
        return $this->type === 'text';
    }

    public function isQualifierCondition(): bool
    {
        return $this->type === 'qualifier';
    }

    public function isTagCondition(): bool
    {
        return $this->type === 'tag';
    }

    public function isQueryCondition(): bool
    {
        return $this->type === 'query';
    }

    /**
     * @return value-of<self::OPERATORS>
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getQualifier(): string
    {
        return $this->qualifier ?? '';
    }

    public function getQuery(): Query
    {
        if ($this->query === null) {
            throw new \LogicException('A "query" condition must be passed.');
        }

        return $this->query;
    }

    public function not(): bool
    {
        return $this->not;
    }

    /**
     * Return whether the text (or the value of the qualifier) must be
     * searched as a phrase (i.e. the words in the given order) rather than
     * as independent words.
     */
    public function isPhrase(): bool
    {
        return $this->phrase;
    }
}
