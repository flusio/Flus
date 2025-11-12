<?php

namespace App\utils;

/**
 * Raised when a requested page is out of the pagination bounds. It is catched
 * by the BaseController to show a "not found" page.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class PaginationOutOfBoundsError extends \RuntimeException
{
}
