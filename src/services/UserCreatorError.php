<?php

namespace flusio\services;

/**
 * Exception raised by the UserCreator service.
 *
 * @see \flusio\services\UserCreator
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserCreatorError extends \RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param array<string, string> $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct('User failed to be created (see $errors)');
        $this->errors = $errors;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
