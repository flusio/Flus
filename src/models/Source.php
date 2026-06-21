<?php

namespace App\models;

use App\utils;

/**
 * A source provides a list of links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
abstract class Source
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function url(): string;

    abstract public function owner(): User;

    /**
     * @return Link[]
     */
    abstract public function links(?utils\Pagination $pagination = null): array;

    abstract public function countLinks(): int;

    abstract public function tagUri(): string;
}
