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

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetOriginFormatter(): void
    {
        OriginFormatter::resetInstance();
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

    public function testOwnerFromOriginWithCollectionUrl(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);

        $collection_owner = $formatter->ownerFromOrigin($origin);

        $this->assertSame($owner->id, $collection_owner?->id);
    }

    public function testOwnerFromOriginWithInaccessibleCollectionUrl(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);

        $collection_owner = $formatter->ownerFromOrigin($origin);

        $this->assertNull($collection_owner);
    }

    public function testOwnerFromOriginWithProfileUrl(): void
    {
        // The label of a profile origin is already the username of its owner.
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $formatter = new OriginFormatter($user);
        $origin = \Minz\Url::absoluteFor('profile', ['id' => $other_user->id]);

        $collection_owner = $formatter->ownerFromOrigin($origin);

        $this->assertNull($collection_owner);
    }

    public function testOwnerFromOriginWithNonUrl(): void
    {
        $user = UserFactory::create();
        $formatter = new OriginFormatter($user);
        $origin = 'The Internet';

        $collection_owner = $formatter->ownerFromOrigin($origin);

        $this->assertNull($collection_owner);
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

    /**
     * The preloaded values are indistinguishable from the ones loaded on the
     * fly: that is the point of the preloading. So, to prove that a value
     * really comes from the memoizer cache, the tests below delete the data
     * from the database after the preloading.
     */
    public function testPreloadOriginsLoadsTheLabelsAndTheOwners(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create(['username' => 'Alix']);
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'name' => 'My collection',
            'type' => 'collection',
            'is_public' => true,
        ]);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        // Two links share the same origin: it must be loaded once for both.
        $link_1 = LinkFactory::create(['origin' => $origin]);
        $link_2 = LinkFactory::create(['origin' => $origin]);
        $formatter = new OriginFormatter($user);

        $formatter->preloadOrigins([$link_1, $link_2]);

        // Without the preloading, the label would fall back to the host and
        // the owner would be null.
        $collection->remove();
        $owner->remove();

        $this->assertSame('My collection', $formatter->labelFromOrigin($origin));
        $this->assertSame('Alix', $formatter->ownerFromOrigin($origin)?->username);
    }

    public function testPreloadOriginsLoadsTheOtherKindsOfOrigins(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create(['username' => 'Alix']);
        $link = LinkFactory::create([
            'title' => 'My link',
            'is_hidden' => false,
        ]);
        $link_origin = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $profile_origin = \Minz\Url::absoluteFor('profile', ['id' => $other_user->id]);
        $link_via_link = LinkFactory::create(['origin' => $link_origin]);
        $link_via_profile = LinkFactory::create(['origin' => $profile_origin]);
        $link_via_website = LinkFactory::create(['origin' => 'https://example.org']);
        $link_via_words = LinkFactory::create(['origin' => 'The Internet']);
        $formatter = new OriginFormatter($user);

        $formatter->preloadOrigins([
            $link_via_link,
            $link_via_profile,
            $link_via_website,
            $link_via_words,
        ]);

        $link->remove();
        $other_user->remove();

        $this->assertSame('My link', $formatter->labelFromOrigin($link_origin));
        $this->assertSame('Alix', $formatter->labelFromOrigin($profile_origin));
        $this->assertSame('example.org', $formatter->labelFromOrigin('https://example.org'));
        $this->assertSame('The Internet', $formatter->labelFromOrigin('The Internet'));
    }

    public function testPreloadOriginsIgnoresInaccessibleOrigins(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'name' => 'My collection',
            'type' => 'collection',
            'is_public' => false,
        ]);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        $link = LinkFactory::create(['origin' => $origin]);
        $formatter = new OriginFormatter($user);

        $formatter->preloadOrigins([$link]);

        // The collection is not preloaded, so the label falls back to the host
        // even though the collection still exists.
        $this->assertSame('test.flus.io', $formatter->labelFromOrigin($origin));
        $this->assertNull($formatter->ownerFromOrigin($origin));
    }

    public function testInstanceReturnsTheSameFormatterForTheSameUser(): void
    {
        $user = UserFactory::create();

        $formatter = OriginFormatter::instance($user);

        $this->assertSame($formatter, OriginFormatter::instance($user));
    }

    public function testInstanceReturnsANewFormatterWhenTheUserChanges(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();

        $other_formatter = OriginFormatter::instance($other_user);
        $formatter = OriginFormatter::instance($user);
        $null_formatter = OriginFormatter::instance(null);

        $this->assertNotSame($formatter, $other_formatter);
        $this->assertNotSame($formatter, $null_formatter);
    }

    public function testResetForgetsTheSharedFormatter(): void
    {
        $user = UserFactory::create();
        $formatter = OriginFormatter::instance($user);

        OriginFormatter::resetInstance();

        $this->assertNotSame($formatter, OriginFormatter::instance($user));
    }
}
