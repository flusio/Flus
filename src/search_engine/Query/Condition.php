<?php

namespace App\search_engine\Query;

/**
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Condition
{
    public const TYPES = ['text', 'qualifier', 'tag'];

    /**
     * @param value-of<self::TYPES> $type
     */
    private function __construct(
        /** @var value-of<self::TYPES> */
        private string $type,
        private string $value,
        private ?string $qualifier,
        private bool $not,
    ) {
    }

    public static function textCondition(string $value): self
    {
        return new self('text', $value, null, false);
    }

    public static function qualifierCondition(string $qualifier, string $value): self
    {
        return new self('qualifier', $value, $qualifier, false);
    }

    public static function tagCondition(string $value, bool $not): self
    {
        return new self('tag', $value, null, $not);
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

    public function getValue(): string
    {
        return $this->value;
    }

    public function getQualifier(): string
    {
        return $this->qualifier ?? '';
    }

    public function not(): bool
    {
        return $this->not;
    }
}
