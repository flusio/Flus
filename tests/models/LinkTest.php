<?php

namespace App\models;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\TimeHelper;

    public function testFetchWithSuccess(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);

        $link->fetch(code: 200);

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertNull($link->fetched_error);
        $this->assertNull($link->fetched_retry_at);
        $this->assertSame(1, $link->fetched_count);
    }

    public function testFetchWithNotFound(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);

        $link->fetch(code: 404, error: 'Page not found');

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(404, $link->fetched_code);
        $this->assertSame('Page not found', $link->fetched_error);
        $this->assertNull($link->fetched_retry_at);
        $this->assertSame(1, $link->fetched_count);
    }

    public function testFetchWithRetryCodeError(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);

        $link->fetch(code: 500, error: 'Internal server error');

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(500, $link->fetched_code);
        $this->assertSame('Internal server error', $link->fetched_error);
        $this->assertEquals(\Minz\Time::fromNow(60, 'seconds'), $link->fetched_retry_at);
        $this->assertSame(1, $link->fetched_count);
    }

    public function testFetchWithRetryCodeErrorAndAlreadyFetched(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);
        $link->fetched_count = 1;

        $link->fetch(code: 500, error: 'Internal server error');

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(500, $link->fetched_code);
        $this->assertSame('Internal server error', $link->fetched_error);
        $this->assertEquals(\Minz\Time::fromNow(61, 'seconds'), $link->fetched_retry_at);
        $this->assertSame(2, $link->fetched_count);
    }

    public function testFetchWithRetryCodeErrorAndRetryAfterLimit(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);

        $link->fetch(
            code: 500,
            error: 'Internal server error',
            retry_after: \Minz\Time::fromNow(120, 'seconds'),
        );

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(500, $link->fetched_code);
        $this->assertSame('Internal server error', $link->fetched_error);
        $this->assertEquals(\Minz\Time::fromNow(120, 'seconds'), $link->fetched_retry_at);
        $this->assertSame(1, $link->fetched_count);
    }

    public function testFetchWithRetryCodeErrorAndFetchAgainAfterRetryAfter(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);

        $link->fetch(
            code: 500,
            error: 'Internal server error',
            retry_after: \Minz\Time::fromNow(30, 'seconds'),
        );

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(500, $link->fetched_code);
        $this->assertSame('Internal server error', $link->fetched_error);
        $this->assertEquals(\Minz\Time::fromNow(60, 'seconds'), $link->fetched_retry_at);
        $this->assertSame(1, $link->fetched_count);
    }

    public function testFetchWithSuccessWithPreviouslyLinkInError(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);
        $link->fetched_code = 500;
        $link->fetched_error = 'Internal server error';
        $link->fetched_retry_at = \Minz\Time::now();
        $link->fetched_count = 1;

        $link->fetch(code: 200);

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertNull($link->fetched_error);
        $this->assertNull($link->fetched_retry_at);
        $this->assertSame(2, $link->fetched_count);
    }

    public function testFetchWithRetryCodeErrorAndMaxRetriesReached(): void
    {
        $this->freeze();
        $link = new Link('https://example.com', 'foo', true);
        $link->fetched_count = Link::FETCHED_RETRIES_MAX_TRIES - 1;

        $link->fetch(code: 500, error: 'Internal server error');

        $this->assertEquals(\Minz\Time::now(), $link->fetched_at);
        $this->assertSame(500, $link->fetched_code);
        $this->assertSame('Internal server error', $link->fetched_error);
        $this->assertSame(Link::FETCHED_RETRIES_MAX_TRIES, $link->fetched_count);
        $this->assertNull($link->fetched_retry_at);
    }
}
