<?php

namespace App\models\users;

use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class HasLinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testObtainLinksCopiesNotOwnedLinksOncePerUrl(): void
    {
        $user = UserFactory::create();
        $url = 'https://example.org/report';
        $link1 = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'url' => $url,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'url' => $url,
        ]);

        $links = $user->obtainLinks([$link1, $link2]);

        $this->assertSame(1, count($links));
        $this->assertFalse($links[0]->isPersisted());
        $this->assertSame($user->id, $links[0]->user_id);
        $this->assertSame($url, $links[0]->url);
    }

    public function testObtainLinksReturnsExistingLinkOncePerUrl(): void
    {
        $user = UserFactory::create();
        $url = 'https://example.org/report';
        $owned_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link1 = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'url' => $url,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'url' => $url,
        ]);

        $links = $user->obtainLinks([$link1, $link2]);

        $this->assertSame(1, count($links));
        $this->assertSame($owned_link->id, $links[0]->id);
    }

    public function testObtainLinksIgnoresNotOwnedLinksWithUrlOwnedInTheList(): void
    {
        $user = UserFactory::create();
        $url = 'https://example.org/report';
        $owned_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'url' => $url,
        ]);

        $links = $user->obtainLinks([$link, $owned_link]);

        $this->assertSame(1, count($links));
        $this->assertSame($owned_link->id, $links[0]->id);
    }

    public function testObtainLinksReturnsExistingLinkEvenIfUrlIsNotSanitized(): void
    {
        $user = UserFactory::create();
        // The URL is not sanitized with the current rules (the scheme should
        // be lowercased).
        $url = 'HTTPS://example.org/report';
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'url' => $url,
        ]);

        $links = $user->obtainLinks([$link]);

        $this->assertSame(1, count($links));
        $this->assertSame($url, $links[0]->url);
        $this->assertSame($link->url_hash, $links[0]->url_hash);
        $this->assertSame($user->id, $links[0]->user_id);
    }
}
