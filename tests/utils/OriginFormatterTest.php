<?php

namespace App\utils;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class OriginFormatterTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initEngine(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    public function testLabelFromOriginWithCollectionUrl(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'name' => 'My collection',
            'type' => 'collection',
            'is_public' => true,
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('My collection', $label);
    }

    public function testLabelFromOriginWithInaccessibleCollectionUrl(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'name' => 'My collection',
            'type' => 'collection',
            'is_public' => false,
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('test.flus.io', $label);
    }

    public function testLabelFromOriginWithLinkUrl(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'title' => 'My link',
            'is_hidden' => false,
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('link', ['id' => $link->id]);

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('My link', $label);
    }

    public function testLabelFromOriginWithInaccessibleLinkUrl(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'title' => 'My link',
            'is_hidden' => true,
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('link', ['id' => $link->id]);

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('test.flus.io', $label);
    }

    public function testLabelFromOriginWithProfileUrl(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create([
            'username' => 'Alix',
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('profile', ['id' => $other_user->id]);

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('Alix', $label);
    }

    public function testLabelFromOriginWithExternalUrl(): void
    {
        $user = UserFactory::create();
        $formatter = new OriginFormatter($user);
        $origin = 'https://example.org';

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('example.org', $label);
    }

    public function testLabelFromOriginWithNonUrl(): void
    {
        $user = UserFactory::create();
        $formatter = new OriginFormatter($user);
        $origin = 'The Internet';

        $label = $formatter->labelFromOrigin($origin);

        $this->assertSame('The Internet', $label);
    }

    public function testUrlFromOriginWithUrl(): void
    {
        $user = UserFactory::create();
        $formatter = new OriginFormatter($user);
        $origin = 'https://example.org';

        $url = $formatter->urlFromOrigin($origin);

        $this->assertSame($origin, $url);
    }

    public function testUrlFromOriginWithNonUrl(): void
    {
        $user = UserFactory::create();
        $formatter = new OriginFormatter($user);
        $origin = 'The Internet';

        $url = $formatter->urlFromOrigin($origin);

        $this->assertSame('', $url);
    }
}
