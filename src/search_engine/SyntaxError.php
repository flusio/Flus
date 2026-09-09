<?php

namespace App\search_engine;

use Minz\Template\TwigExtension;

/**
 * Raised when a search query typed by the user cannot be interpreted.
 *
 * The syntax is lenient (see Query\Parser): the only error is a qualifier
 * used with a value it doesn't accept.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SyntaxError extends \RuntimeException
{
    public const QUALIFIER_VALUE_INVALID = 1;

    private function __construct(
        string $message,
        int $code,
        private int $position,
        private string $value = '',
    ) {
        parent::__construct("Syntax error: {$message}", $code);
    }

    public static function qualifierValueInvalid(int $position, string $qualifier, string $value): self
    {
        return new self(
            "qualifier \"{$qualifier}\" does not accept the value \"{$value}\" at char {$position}",
            self::QUALIFIER_VALUE_INVALID,
            $position,
            "{$qualifier}:{$value}",
        );
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Return the message to display to the user.
     */
    public function translatedMessage(): string
    {
        $code = $this->getCode();

        if ($code === self::QUALIFIER_VALUE_INVALID) {
            return TwigExtension::translate(
                'Incorrect qualifier value “%s” at character %d.',
                [$this->value, $this->position],
            );
        } else {
            throw new \LogicException("Unknown syntax error code {$code}");
        }
    }
}
