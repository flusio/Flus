<?php

namespace App\search_engine;

use Minz\Database;

/**
 * Build the SQL conditions matching a Query.
 *
 * The child classes declare the qualifiers they accept and build the
 * expressions specific to the searched data (text, tags and qualifiers),
 * while this class combines them and provides generic helpers (full-text,
 * LIKE, dates, numbers).
 *
 * @author  Probesys <https://github.com/Probesys/bileto>
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
abstract class QueryBuilder
{
    /**
     * The syntax of a date: a year, a month or a day, or a keyword
     * designating the current one.
     */
    public const DATE_REGEX = '(?:'
        . '\d{4}(?:-(?:0[1-9]|1[0-2])(?:-(?:0[1-9]|[12]\d|3[01]))?)?'
        . '|today|current-month|current-year'
        . ')';

    /**
     * The syntax of the value of a date qualifier: a date, possibly prefixed
     * by a comparison operator, or a range of dates.
     */
    public const DATE_VALUE_REGEX = '/^(?:'
        . '(?:>=|>|<=|<)?' . self::DATE_REGEX
        . '|' . self::DATE_REGEX . '\.\.' . self::DATE_REGEX
        . ')$/';

    /**
     * The syntax of the value of a numeric qualifier: a number, possibly
     * prefixed by a comparison operator, or a range of numbers.
     */
    public const NUMBER_VALUE_REGEX = '/^(?:'
        . '(?:>=|>|<=|<)?\d{1,9}'
        . '|\d{1,9}\.\.\d{1,9}'
        . ')$/';

    /** @var array<string, mixed> */
    protected array $parameters = [];

    /**
     * @param literal-string $alias
     *     The alias given to the searched table in the request.
     */
    public function __construct(
        protected string $alias,
    ) {
    }

    /**
     * Return the SQL conditions matching the given query, to be appended to
     * the WHERE clause of the request, along with their parameters.
     *
     * The conditions start with " AND " and are wrapped in parentheses. An
     * empty string is returned if the query has no condition.
     *
     * @return array{literal-string, array<string, mixed>}
     */
    public function build(Query $query): array
    {
        $this->parameters = [];

        $where_sql = $this->buildWhere($query);

        if ($where_sql === '') {
            // The query has no condition: nothing is filtered.
            return ['', []];
        }

        return [" AND ({$where_sql})", $this->parameters];
    }

    /**
     * Combine the expressions of the conditions with their operators.
     *
     * @return literal-string
     */
    protected function buildWhere(Query $query): string
    {
        $where_sql = '';

        $conditions = $this->mergeTextConditions($query->getConditions());

        foreach ($conditions as $condition) {
            $expr = $this->buildConditionExpr($condition);

            if ($where_sql === '') {
                $where_sql = $expr;
            } elseif ($condition->getOperator() === 'or') {
                $where_sql .= " OR {$expr}";
            } else {
                $where_sql .= " AND {$expr}";
            }
        }

        return $where_sql;
    }

    /**
     * Merge the text conditions combined by AND into a single one.
     *
     * The words are searched together (rather than one expression per word)
     * so that PostgreSQL handles the stop words as expected: they are
     * ignored in "le chat", while a query made only of stop words matches
     * nothing.
     *
     * Only the words of a same "run" (i.e. a sequence of conditions combined
     * by AND, delimited by OR) are merged, so that the priority between AND
     * and OR is preserved. The negated words and the phrases are not merged
     * either. The merged condition takes the place of the first word of the
     * run.
     *
     * @param Query\Condition[] $conditions
     *
     * @return Query\Condition[]
     */
    protected function mergeTextConditions(array $conditions): array
    {
        $merged_conditions = [];
        // The index of the merged text condition of the current run, in the
        // list of merged conditions.
        $text_index = null;

        foreach ($conditions as $condition) {
            if ($condition->getOperator() === 'or') {
                // A new run starts.
                $text_index = null;
            }

            if (!$condition->isTextCondition() || $condition->not() || $condition->isPhrase()) {
                $merged_conditions[] = $condition;
            } elseif ($text_index === null) {
                $text_index = count($merged_conditions);
                $merged_conditions[] = $condition;
            } else {
                $text_condition = $merged_conditions[$text_index];
                $merged_conditions[$text_index] = Query\Condition::textCondition(
                    $text_condition->getOperator(),
                    $text_condition->getValue() . ' ' . $condition->getValue(),
                    not: false,
                );
            }
        }

        return $merged_conditions;
    }

    /**
     * @return literal-string
     */
    protected function buildConditionExpr(Query\Condition $condition): string
    {
        if ($condition->isTextCondition()) {
            return $this->buildTextExpr($condition);
        } elseif ($condition->isQualifierCondition()) {
            return $this->buildQualifierExpr($condition);
        } elseif ($condition->isTagCondition()) {
            return $this->buildTagExpr($condition);
        } elseif ($condition->isQueryCondition()) {
            return $this->buildQueryExpr($condition);
        }

        throw new \LogicException('A condition is defective as it generates no expression');
    }

    /**
     * @return literal-string
     */
    abstract protected function buildTextExpr(Query\Condition $condition): string;

    /**
     * @return literal-string
     */
    abstract protected function buildTagExpr(Query\Condition $condition): string;

    /**
     * @return literal-string
     */
    abstract protected function buildQualifierExpr(Query\Condition $condition): string;

    /**
     * @return literal-string
     */
    protected function buildQueryExpr(Query\Condition $condition): string
    {
        $where_sql = $this->buildWhere($condition->getQuery());

        return $condition->not() ? "NOT ({$where_sql})" : "({$where_sql})";
    }

    /**
     * Return an expression matching the values of a tsvector field
     * containing the given text.
     *
     * If `$phrase` is true, the words must follow each other in the given
     * order, otherwise they can be anywhere in the field.
     *
     * @param literal-string $field
     *
     * @return literal-string
     */
    protected function buildExprFullText(string $field, string $value, bool $not, bool $phrase): string
    {
        $parameter_name = $this->registerParameter($value);

        if ($phrase) {
            $expr = "{$field} @@ phraseto_tsquery('french', {$parameter_name})";
        } else {
            $expr = "{$field} @@ plainto_tsquery('french', {$parameter_name})";
        }

        return $not ? "NOT ({$expr})" : $expr;
    }

    /**
     * Return an expression matching the values containing the given text.
     *
     * @param literal-string $field
     *
     * @return literal-string
     */
    protected function buildExprLike(string $field, string $value, bool $not): string
    {
        // The wildcards must be escaped so that they are searched literally.
        $value = addcslashes($value, '\\%_');

        $parameter_name = $this->registerParameter("%{$value}%");

        return $not ? "{$field} NOT ILIKE {$parameter_name}" : "{$field} ILIKE {$parameter_name}";
    }

    /**
     * Return an expression comparing a date field to the given value.
     *
     * The value follows the syntax of self::DATE_VALUE_REGEX. A
     * date designates a period: "2026" is the whole year, "2026-03" the whole
     * month and "2026-03-23" the whole day. The comparison operators apply to
     * the bounds of this period, e.g. ">2026-03" means "after the end of
     * March 2026".
     *
     * @param literal-string $field
     *
     * @return literal-string
     */
    protected function buildExprDate(string $field, string $value, bool $not): string
    {
        if (str_contains($value, '..')) {
            list($from, $to) = explode('..', $value, 2);
            list($start) = $this->parseDatePeriod($from);
            list(, $end) = $this->parseDatePeriod($to);

            $expr = $this->buildExprBetween($field, $start, $end);
        } else {
            $result = preg_match('/^(?P<operator>>=|>|<=|<)?(?P<date>.+)$/', $value, $matches);
            assert($result === 1);

            $operator = $matches['operator'];
            list($start, $end) = $this->parseDatePeriod($matches['date']);

            if ($operator === '>=') {
                $expr = $this->buildExprCompare($field, '>=', $start);
            } elseif ($operator === '>') {
                $expr = $this->buildExprCompare($field, '>', $end);
            } elseif ($operator === '<=') {
                $expr = $this->buildExprCompare($field, '<=', $end);
            } elseif ($operator === '<') {
                $expr = $this->buildExprCompare($field, '<', $start);
            } else {
                $expr = $this->buildExprBetween($field, $start, $end);
            }
        }

        return $not ? "NOT ({$expr})" : $expr;
    }

    /**
     * Return the period designated by a date, as its start and its end (both
     * included, with a precision of a second).
     *
     * @return array{string, string}
     */
    protected function parseDatePeriod(string $date): array
    {
        $now = \Minz\Time::now();

        if ($date === 'today') {
            $start = $now->format('Y-m-d');
            $unit = 'day';
        } elseif ($date === 'current-month') {
            $start = $now->format('Y-m-01');
            $unit = 'month';
        } elseif ($date === 'current-year') {
            $start = $now->format('Y-01-01');
            $unit = 'year';
        } elseif (strlen($date) === 4) {
            $start = "{$date}-01-01";
            $unit = 'year';
        } elseif (strlen($date) === 7) {
            $start = "{$date}-01";
            $unit = 'month';
        } else {
            $start = $date;
            $unit = 'day';
        }

        $start = new \DateTimeImmutable("{$start} 00:00:00");
        $end = $start->modify("+1 {$unit} -1 second");

        return [
            $start->format(Database\Column::DATETIME_FORMAT),
            $end->format(Database\Column::DATETIME_FORMAT),
        ];
    }

    /**
     * Return an expression comparing a numeric field to the given value.
     *
     * The value follows the syntax of self::NUMBER_VALUE_REGEX.
     *
     * @param literal-string $field
     *
     * @return literal-string
     */
    protected function buildExprNumber(string $field, string $value, bool $not): string
    {
        if (str_contains($value, '..')) {
            list($from, $to) = explode('..', $value, 2);

            $expr = $this->buildExprBetween($field, intval($from), intval($to));
        } else {
            $result = preg_match('/^(?P<operator>>=|>|<=|<)?(?P<number>\d+)$/', $value, $matches);
            assert($result === 1);

            $operator = $matches['operator'] !== '' ? $matches['operator'] : '=';
            $number = intval($matches['number']);

            $expr = $this->buildExprCompare($field, $operator, $number);
        }

        return $not ? "NOT ({$expr})" : $expr;
    }

    /**
     * @param literal-string $field
     * @param '='|'>='|'>'|'<='|'<' $operator
     *
     * @return literal-string
     */
    protected function buildExprCompare(string $field, string $operator, mixed $value): string
    {
        $parameter_name = $this->registerParameter($value);

        return "{$field} {$operator} {$parameter_name}";
    }

    /**
     * Return an expression checking that a field is between two values (both
     * included).
     *
     * @param literal-string $field
     *
     * @return literal-string
     */
    protected function buildExprBetween(string $field, mixed $from, mixed $to): string
    {
        $parameter_name_from = $this->registerParameter($from);
        $parameter_name_to = $this->registerParameter($to);

        return "{$field} BETWEEN {$parameter_name_from} AND {$parameter_name_to}";
    }

    /**
     * Add a value to the list of the parameters and return the name under
     * which it is registered.
     *
     * The name is numbered so it doesn't conflict with the other parameters.
     *
     * @return literal-string
     */
    protected function registerParameter(mixed $value): string
    {
        $parameter_name = ':search_param' . (count($this->parameters) + 1);

        $this->parameters[$parameter_name] = $value;

        // The name is built from a literal prefix and a counter: it never
        // contains anything coming from the query, but PHPStan cannot infer it.
        /** @phpstan-ignore return.type */
        return $parameter_name;
    }
}
