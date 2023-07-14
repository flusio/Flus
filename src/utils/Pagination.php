<?php

namespace flusio\utils;

/**
 * Facilitates pagination manipulations.
 *
 * @phpstan-type PaginationPage array{'type': 'number', 'number': int} | array{'type': 'ellipsis'}
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pagination
{
    private int $number_elements;

    private int $number_per_page;

    private int $total_pages;

    private int $current_page;

    private int $current_offset;

    /**
     * Initialize a pagination object.
     *
     * current_page is bounded between 1 and total_pages.
     */
    public function __construct(int $number_elements, int $number_per_page, int $current_page)
    {
        $this->number_elements = $number_elements;
        if ($number_per_page < 1) {
            $this->number_per_page = 1;
        } else {
            $this->number_per_page = $number_per_page;
        }
        $this->total_pages = max(1, intval(ceil($this->number_elements / $this->number_per_page)));
        if ($current_page < 1) {
            $this->current_page = 1;
        } elseif ($current_page > $this->total_pages) {
            $this->current_page = $this->total_pages;
        } else {
            $this->current_page = $current_page;
        }
        $this->current_offset = $this->number_per_page * ($this->current_page - 1);
    }

    public function numberElements(): int
    {
        return $this->number_elements;
    }

    public function totalPages(): int
    {
        return $this->total_pages;
    }

    public function currentPage(): int
    {
        return $this->current_page;
    }

    public function currentOffset(): int
    {
        return $this->current_offset;
    }

    public function numberPerPage(): int
    {
        return $this->number_per_page;
    }

    /**
     * Return true if there is more than one page.
     */
    public function mustPaginate(): bool
    {
        return $this->total_pages > 1;
    }

    /**
     * Return true if current_page is equal to 1
     */
    public function isCurrentFirstPage(): bool
    {
        return $this->current_page === 1;
    }

    /**
     * Return true if current_page is equal to total_pages
     */
    public function isCurrentLastPage(): bool
    {
        return $this->current_page === $this->total_pages;
    }

    /**
     * Return true if the given page is equal to current_page
     */
    public function isPageCurrent(int $page_number): bool
    {
        return $this->current_page === $page_number;
    }

    public function previousPageNumber(): int
    {
        if ($this->isCurrentFirstPage()) {
            return $this->current_page;
        } else {
            return $this->current_page - 1;
        }
    }

    public function nextPageNumber(): int
    {
        if ($this->isCurrentLastPage()) {
            return $this->current_page;
        } else {
            return $this->current_page + 1;
        }
    }

    /**
     * Return the list of pages to paginate.
     *
     * Each elements of the returned array is an array itself, where:
     *
     * - `type` is equal to `number` or `ellipsis`
     * - `number` is the page number (only when `type` is `number`)
     *
     * Ellipsis are returned for total_pages greater than 5. Above 5, first,
     * last, current and current+1 pages are returned. Ellipsis are added
     * between numbers that don't follow.
     *
     * For instance, for a total_pages = 7 and current_page = 4:
     *
     *     [
     *         ['type' => 'number', 'number' => 1],
     *         ['type' => 'ellipsis'],
     *         ['type' => 'number', 'number' => 4],
     *         ['type' => 'number', 'number' => 5],
     *         ['type' => 'ellipsis'],
     *         ['type' => 'number', 'number' => 7],
     *     ]
     *
     * @return \Generator<int, PaginationPage, void, void>
     */
    public function pages(): \Generator
    {
        if ($this->total_pages <= 5) {
            foreach (range(1, $this->total_pages) as $page_number) {
                yield ['type' => 'number', 'number' => $page_number];
            }
            return;
        }

        yield ['type' => 'number', 'number' => 1];

        if ($this->current_page === 1 || $this->current_page === 2) {
            yield ['type' => 'number', 'number' => 2];
            yield ['type' => 'number', 'number' => 3];
            yield ['type' => 'ellipsis'];
        } elseif ($this->current_page === 3) {
            yield ['type' => 'number', 'number' => 2];
            yield ['type' => 'number', 'number' => 3];
            yield ['type' => 'number', 'number' => 4];
            yield ['type' => 'ellipsis'];
        } elseif ($this->current_page < $this->total_pages - 2) {
            yield ['type' => 'ellipsis'];
            yield ['type' => 'number', 'number' => $this->current_page - 1];
            yield ['type' => 'number', 'number' => $this->current_page];
            yield ['type' => 'number', 'number' => $this->current_page + 1];
            yield ['type' => 'ellipsis'];
        } elseif ($this->current_page === $this->total_pages - 2) {
            yield ['type' => 'ellipsis'];
            yield ['type' => 'number', 'number' => $this->total_pages - 3];
            yield ['type' => 'number', 'number' => $this->total_pages - 2];
            yield ['type' => 'number', 'number' => $this->total_pages - 1];
        } else {
            yield ['type' => 'ellipsis'];
            yield ['type' => 'number', 'number' => $this->total_pages - 2];
            yield ['type' => 'number', 'number' => $this->total_pages - 1];
        }

        yield ['type' => 'number', 'number' => $this->total_pages];
    }
}
