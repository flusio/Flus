<?php

namespace App\navigations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ItemPlaceholder extends Element
{
    public function __construct(
        public readonly string $label,
    ) {
    }

    public function is(string $type): bool
    {
        return $type === 'placeholder';
    }
}
