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

    public function testFromWithNoRefererButPreviousUrlInSession(): void
    {
        $self_uri = '/news';
        $previous_url = '/bookmarks';
        $_SESSION['_previous_url'] = $previous_url;
        $request = new Request('POST', $self_uri);

        $from = RequestHelper::from($request);

        unset($_SESSION['_previous_url']);
        $this->assertSame($previous_url, $from);
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

    public function testSetPreviousUrlWithStandardGetRequest(): void
    {
        $self_uri = '/news';
        $request = new Request('GET', $self_uri);

        RequestHelper::setPreviousUrl($request);

        $this->assertSame($self_uri, $_SESSION['_previous_url'] ?? '');
        unset($_SESSION['_previous_url']);
    }

    public function testSetPreviousUrlWithRequestedModal(): void
    {
        $self_uri = '/news';
        $request = new Request('GET', $self_uri, headers: [
            'Turbo-Frame' => 'modal-content',
        ]);

        RequestHelper::setPreviousUrl($request);

        $this->assertArrayNotHasKey('_previous_url', $_SESSION);
    }

    public function testSetPreviousUrlWithPostRequest(): void
    {
        $self_uri = '/news';
        $request = new Request('POST', $self_uri);

        RequestHelper::setPreviousUrl($request);

        $this->assertArrayNotHasKey('_previous_url', $_SESSION);
    }
}
