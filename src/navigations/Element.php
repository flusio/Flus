<?php

namespace App\navigations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
abstract class Element
{
    abstract public function is(string $type): bool;
}
