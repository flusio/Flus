<?php

namespace App\navigations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
abstract class BaseNavigation
{
    public function __construct(
        private string $current,
    ) {
    }

    public function currentLabel(): string
    {
        $items = [];
        foreach ($this->elements() as $element) {
            if ($element instanceof ItemGroup) {
                $items = array_merge($items, $element->items);
            } elseif ($element instanceof Item) {
                $items[] = $element;
            }
        }

        foreach ($items as $item) {
            if ($this->isCurrent($item)) {
                return $item->label;
            }
        }

        return '';
    }

    public function isCurrent(Item $item): bool
    {
        return $item->key === $this->current;
    }

    /**
     * @return Element[]
     */
    abstract public function elements(): array;
}
