<?php

namespace App\utils;

use Minz\Request;

class RequestHelperTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;

    public function testFromWithStandardGetRequest(): void
    {
        $self_uri = '/news';
        $referer = '/bookmarks';
        $request = new Request('GET', $self_uri, headers: [
            'Referer' => $referer,
        ]);

        $from = RequestHelper::from($request);

        $this->assertSame($self_uri, $from);
    }

    public function testFromWithPostRequest(): void
    {
        $self_uri = '/news';
        $referer = '/bookmarks';
        $request = new Request('POST', $self_uri, headers: [
            'Referer' => $referer,
        ]);

        $from = RequestHelper::from($request);

        $this->assertSame($referer, $from);
    }

    public function testFromWithRequestedModal(): void
    {
        $self_uri = '/news';
        $referer = '/bookmarks';
        $request = new Request('GET', $self_uri, headers: [
            'Referer' => $referer,
            'Turbo-Frame' => 'modal-content',
        ]);

        $from = RequestHelper::from($request);

        $this->assertSame($referer, $from);
    }

    public function testFromWithNoReferer(): void
    {
        $self_uri = '/news';
        $request = new Request('POST', $self_uri);

        $from = RequestHelper::from($request);

        $this->assertSame($self_uri, $from);
    }

    public function testFromWithNotRedirectableReferer(): void
    {
        $self_uri = '/news';
        $referer = 'https://bad.example.com';
        $request = new Request('POST', $self_uri, headers: [
            'Referer' => $referer,
        ]);

        $from = RequestHelper::from($request);

        $this->assertSame('/', $from);
    }
}
