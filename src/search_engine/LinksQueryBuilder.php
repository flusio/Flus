<?php

namespace App\search_engine;

/**
 * Build the SQL conditions matching a Query, for a request on the links
 * table.
 *
 * @phpstan-import-type Qualifiers from Query\Parser
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksQueryBuilder extends QueryBuilder
{
    /**
     * The qualifiers accepted to search the links of a user.
     *
     * @var Qualifiers
     */
    public const LINKS_QUALIFIERS = [
        'url' => '@text',
        'is' => ['hidden'],
        'has' => ['notes', 'tags'],
        'no' => ['notes', 'tags'],
        'date' => self::DATE_VALUE_REGEX,
        'duration' => self::NUMBER_VALUE_REGEX,
        'notes' => '@text',
    ];

    /**
     * The qualifiers accepted to search the links of a stream.
     *
     * The links of a stream are the ones of its sources, not (necessarily) the
     * copies of the user: the qualifiers about the notes, the tags and the
     * visibility would search in the data of the sources, which is confusing.
     * The qualifier about the date is redundant with the period of the stream.
     * They are not listed, so they are read as text. For the same reason, the
     * "#tags" are not searchable in a stream (see LinksSearcher::buildQuery()).
     *
     * @var Qualifiers
     */
    public const STREAM_QUALIFIERS = [
        'url' => '@text',
        'duration' => self::NUMBER_VALUE_REGEX,
    ];

    /**
     * @return literal-string
     */
    protected function buildTextExpr(Query\Condition $condition): string
    {
        return $this->buildExprFullText(
            "{$this->alias}.search_index",
            $condition->getValue(),
            $condition->not(),
            $condition->isPhrase(),
        );
    }

    /**
     * @return literal-string
     */
    protected function buildTagExpr(Query\Condition $condition): string
    {
        $parameter_name = $this->registerParameter(mb_strtolower($condition->getValue()));

        // The tags column is a JSONB object which keys are the lowercased
        // tags. The "?" operator checks that a key exists. It is doubled so
        // that PDO doesn't interpret it as a placeholder.
        $expr = "{$this->alias}.tags ?? {$parameter_name}";

        return $condition->not() ? "NOT ({$expr})" : $expr;
    }

    /**
     * @return literal-string
     */
    protected function buildQualifierExpr(Query\Condition $condition): string
    {
        $qualifier = $condition->getQualifier();
        $value = $condition->getValue();

        if ($qualifier === 'url') {
            return $this->buildUrlQualifierExpr($condition);
        } elseif ($qualifier === 'is' && $value === 'hidden') {
            return $this->buildIsHiddenQualifierExpr($condition);
        } elseif ($qualifier === 'has' && $value === 'notes') {
            return $this->buildHasNotesQualifierExpr($condition);
        } elseif ($qualifier === 'no' && $value === 'notes') {
            return $this->buildNoNotesQualifierExpr($condition);
        } elseif ($qualifier === 'has' && $value === 'tags') {
            return $this->buildHasTagsQualifierExpr($condition);
        } elseif ($qualifier === 'no' && $value === 'tags') {
            return $this->buildNoTagsQualifierExpr($condition);
        } elseif ($qualifier === 'date') {
            return $this->buildDateQualifierExpr($condition);
        } elseif ($qualifier === 'duration') {
            return $this->buildDurationQualifierExpr($condition);
        } elseif ($qualifier === 'notes') {
            return $this->buildNotesQualifierExpr($condition);
        }

        // The Tokenizer and the Parser only accept the qualifiers and values
        // listed in self::QUALIFIERS, so this should never happen.
        throw new \LogicException("Unexpected \"{$qualifier}:{$value}\" qualifier");
    }

    /**
     * @return literal-string
     */
    private function buildUrlQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprLike("{$this->alias}.url", $condition->getValue(), $condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildIsHiddenQualifierExpr(Query\Condition $condition): string
    {
        return $condition->not() ? "{$this->alias}.is_hidden = false" : "{$this->alias}.is_hidden = true";
    }

    /**
     * @return literal-string
     */
    private function buildHasNotesQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprNotesExist(exist: !$condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildNoNotesQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprNotesExist(exist: $condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildHasTagsQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprTagsExist(exist: !$condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildNoTagsQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprTagsExist(exist: $condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildDateQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprDate("{$this->alias}.created_at", $condition->getValue(), $condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildDurationQualifierExpr(Query\Condition $condition): string
    {
        return $this->buildExprNumber("{$this->alias}.reading_time", $condition->getValue(), $condition->not());
    }

    /**
     * @return literal-string
     */
    private function buildNotesQualifierExpr(Query\Condition $condition): string
    {
        $expr = $this->buildExprFullText(
            'n.search_index',
            $condition->getValue(),
            not: false,
            phrase: $condition->isPhrase(),
        );

        return $this->buildExprNotesExist(exist: !$condition->not(), where: $expr);
    }

    /**
     * Return an expression matching the links having (or not) notes. The
     * notes can be filtered with an additional condition, in which the notes
     * table is aliased "n".
     *
     * @param ?literal-string $where
     *
     * @return literal-string
     */
    private function buildExprNotesExist(bool $exist, ?string $where = null): string
    {
        $where_sql = "n.link_id = {$this->alias}.id";

        if ($where !== null) {
            $where_sql .= " AND {$where}";
        }

        $expr = "EXISTS (SELECT 1 FROM notes n WHERE {$where_sql})";

        return $exist ? $expr : "NOT {$expr}";
    }

    /**
     * Return an expression matching the links having (or not) tags.
     *
     * @return literal-string
     */
    private function buildExprTagsExist(bool $exist): string
    {
        // The tags are stored as a JSONB object, except when there are none:
        // the empty list is stored as an empty array.
        return $exist ? "{$this->alias}.tags != '[]'::jsonb" : "{$this->alias}.tags = '[]'::jsonb";
    }
}
