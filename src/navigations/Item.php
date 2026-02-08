<?php

namespace App\navigations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Item extends Element
{
    public function __construct(
        public readonly string $key,
        public readonly string $url,
        public readonly string $icon,
        public readonly string $label,
    ) {
    }

    public function is(string $type): bool
    {
        return $type === 'item';
    }
}
