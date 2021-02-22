<?php

namespace flusio\utils;

/**
 * Facilitates pagination manipulations.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pagination
{
    /** @var integer */
    private $number_elements;

    /** @var integer */
    private $number_per_page;

    /** @var integer */
    private $total_pages;

    /** @var integer */
    private $current_page;

    /** @var integer */
    private $current_offset;

    /**
     * Initialize a pagination object.
     *
     * current_page is bounded between 1 and total_pages.
     *
     * @param integer $number_elements
     * @param integer $number_per_page
     * @param integer $current_page
     */
    public function __construct($number_elements, $number_per_page, $current_page)
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

    /**
     * @return integer
     */
    public function totalPages()
    {
        return $this->total_pages;
    }

    /**
     * @return integer
     */
    public function currentPage()
    {
        return $this->current_page;
    }

    /**
     * @return integer
     */
    public function currentOffset()
    {
        return $this->current_offset;
    }

    /**
     * @return integer
     */
    public function numberPerPage()
    {
        return $this->number_per_page;
    }

    /**
     * @return boolean True if there is more than one page.
     */
    public function mustPaginate()
    {
        return $this->total_pages > 1;
    }

    /**
     * @return boolean True if current_page is equal to 1
     */
    public function isCurrentFirstPage()
    {
        return $this->current_page === 1;
    }

    /**
     * @return boolean True if current_page is equal to total_pages
     */
    public function isCurrentLastPage()
    {
        return $this->current_page === $this->total_pages;
    }

    /**
     * @return boolean True if the given page is equal to current_page
     */
    public function isPageCurrent($page_number)
    {
        return $this->current_page === $page_number;
    }

    /**
     * @return integer The page before current_page
     */
    public function previousPageNumber()
    {
        if ($this->isCurrentFirstPage()) {
            return $this->current_page;
        } else {
            return $this->current_page - 1;
        }
    }

    /**
     * @return integer The page after current_page
     */
    public function nextPageNumber()
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
     * ```php
     * [
     *     ['type' => 'number', 'number' => 1],
     *     ['type' => 'ellipsis'],
     *     ['type' => 'number', 'number' => 4],
     *     ['type' => 'number', 'number' => 5],
     *     ['type' => 'ellipsis'],
     *     ['type' => 'number', 'number' => 7],
     * ]
     *
     * @yield array
     */
    public function pages()
    {
        if ($this->total_pages <= 5) {
            foreach (range(1, $this->total_pages) as $page_number) {
                yield ['type' => 'number', 'number' => $page_number];
            }
            return;
        }

        yield ['type' => 'number', 'number' => 1];

        if ($this->current_page >= 1 && $this->current_page <= 2) {
            yield ['type' => 'number', 'number' => 2];
            yield ['type' => 'number', 'number' => 3];
            yield ['type' => 'ellipsis'];
        } elseif ($this->current_page < $this->total_pages - 2) {
            yield ['type' => 'ellipsis'];
            yield ['type' => 'number', 'number' => $this->current_page];
            yield ['type' => 'number', 'number' => $this->current_page + 1];
            yield ['type' => 'ellipsis'];
        } else {
            yield ['type' => 'ellipsis'];
            yield ['type' => 'number', 'number' => $this->total_pages - 2];
            yield ['type' => 'number', 'number' => $this->total_pages - 1];
        }

        yield ['type' => 'number', 'number' => $this->total_pages];
    }
}
