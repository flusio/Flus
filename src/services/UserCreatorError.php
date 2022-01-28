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
    private $errors;

    public function __construct($errors)
    {
        parent::__construct('User failed to be created (see $errors)');
        $this->errors = $errors;
    }

    public function errors()
    {
        return $this->errors;
    }
}
