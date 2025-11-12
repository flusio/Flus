<?php

namespace App\utils;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorFailsIfCurrentPageIsLessThan1(): void
    {
        $this->expectException(PaginationOutOfBoundsError::class);

        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 0;

        new Pagination($number_elements, $number_per_page, $initial_current_page);
    }

    public function testConstructorFailsIfCurrentPageIsMoreThanTotalPages(): void
    {
        $this->expectException(PaginationOutOfBoundsError::class);

        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 11;

        new Pagination($number_elements, $number_per_page, $initial_current_page);
    }

    public function testNumberElements(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $number_elements = $pagination->numberElements();

        $this->assertSame(300, $number_elements);
    }

    public function testTotalPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $total_pages = $pagination->totalPages();

        $this->assertSame(10, $total_pages);
    }

    public function testTotalPageIsBoundedBy1(): void
    {
        $number_elements = 0;
        $number_per_page = 30;
        $initial_current_page = 1;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $total_pages = $pagination->totalPages();

        $this->assertSame(1, $total_pages);
    }

    public function testCurrentPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $current_page = $pagination->currentPage();

        $this->assertSame($initial_current_page, $current_page);
    }

    public function testCurrentOffset(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $current_offset = $pagination->currentOffset();

        $this->assertSame(60, $current_offset);
    }

    public function testNumberPerPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $number_per_page = $pagination->numberPerPage();

        $this->assertSame(30, $number_per_page);
    }

    public function testNumberPerPageIsBoundedBy1(): void
    {
        $number_elements = 300;
        $number_per_page = 0;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $number_per_page = $pagination->numberPerPage();

        $this->assertSame(1, $number_per_page);
    }

    public function testMustPaginateReturnsTrueIfTotalPagesIsGreaterThan1(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $must_paginate = $pagination->mustPaginate();

        $this->assertTrue($must_paginate);
    }

    public function testMustPaginateReturnsFalseIfTotalPagesIsEqualOrLowerThan1(): void
    {
        $number_elements = 30;
        $number_per_page = 30;
        $initial_current_page = 1;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $must_paginate = $pagination->mustPaginate();

        $this->assertFalse($must_paginate);
    }

    public function testIsCurrentFirstPageReturnsTrueIfCurrentPageIs1(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 1;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $is_first_page = $pagination->isCurrentFirstPage();

        $this->assertTrue($is_first_page);
    }

    public function testIsCurrentFirstPageReturnsFalseIfCurrentPageIsGreaterThan1(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 2;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $is_first_page = $pagination->isCurrentFirstPage();

        $this->assertFalse($is_first_page);
    }

    public function testIsCurrentLastPageReturnsTrueIfCurrentPageIsLastPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 10;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $is_last_page = $pagination->isCurrentLastPage();

        $this->assertTrue($is_last_page);
    }

    public function testIsCurrentLastPageReturnsFalseIfCurrentPageIsLowerThanLastPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 9;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $is_last_page = $pagination->isCurrentLastPage();

        $this->assertFalse($is_last_page);
    }

    public function testIsPageCurrentReturnsTrueIfGivenPageIsCurrentPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $is_current = $pagination->isPageCurrent(3);

        $this->assertTrue($is_current);
    }

    public function testIsPageCurrentReturnsFalseIfGivenPageIsDifferentThanCurrentPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $is_current = $pagination->isPageCurrent(2);

        $this->assertFalse($is_current);
    }

    public function testPreviousPageNumber(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $previous_number = $pagination->previousPageNumber();

        $this->assertSame(2, $previous_number);
    }

    public function testPreviousPageNumberReturnsCurrentPageIfFirstPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 1;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $previous_number = $pagination->previousPageNumber();

        $this->assertSame(1, $previous_number);
    }

    public function testNextPageNumber(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $next_number = $pagination->nextPageNumber();

        $this->assertSame(4, $next_number);
    }

    public function testNextPageNumberReturnsCurrentPageIfLastPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 10;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $next_number = $pagination->nextPageNumber();

        $this->assertSame(10, $next_number);
    }

    public function testPagesWhenTotalPagesIs5OrLower(): void
    {
        $number_elements = 150;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'number', 'number' => 2],
            ['type' => 'number', 'number' => 3],
            ['type' => 'number', 'number' => 4],
            ['type' => 'number', 'number' => 5],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsFirstPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 1;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'number', 'number' => 2],
            ['type' => 'number', 'number' => 3],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsSecondPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 2;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'number', 'number' => 2],
            ['type' => 'number', 'number' => 3],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsThirdPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 3;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'number', 'number' => 2],
            ['type' => 'number', 'number' => 3],
            ['type' => 'number', 'number' => 4],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsInMiddle(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 5;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 4],
            ['type' => 'number', 'number' => 5],
            ['type' => 'number', 'number' => 6],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsThirdToLastPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 8;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 7],
            ['type' => 'number', 'number' => 8],
            ['type' => 'number', 'number' => 9],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsSecondToLastPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 9;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 8],
            ['type' => 'number', 'number' => 9],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }

    public function testPagesWhenCurrentPageIsLastPage(): void
    {
        $number_elements = 300;
        $number_per_page = 30;
        $initial_current_page = 10;
        $pagination = new Pagination($number_elements, $number_per_page, $initial_current_page);

        $pages = iterator_to_array($pagination->pages());

        $this->assertSame([
            ['type' => 'number', 'number' => 1],
            ['type' => 'ellipsis'],
            ['type' => 'number', 'number' => 8],
            ['type' => 'number', 'number' => 9],
            ['type' => 'number', 'number' => 10],
        ], $pages);
    }
}
