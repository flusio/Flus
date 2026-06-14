<?php

namespace App\models\dao\links;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class NewsQueriesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;

    private models\User $user;
    private models\User $other_user;

    #[\PHPUnit\Framework\Attributes\Before]
    public function setUsers(): void
    {
        $this->user = UserFactory::create();
        $this->other_user = UserFactory::create();
    }

    public function testListFromFollowedCollectionsSelectsFromFollowed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $published_at1 = \Minz\Time::ago(3, 'days');
        $published_at2 = \Minz\Time::ago(1, 'days');
        $link1 = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link1], at: $published_at1);
        $collection->addLinks([$link2], at: $published_at2);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(2, count($links));
        $this->assertSame($link2->id, $links[0]->id);
        $this->assertSame($collection->id, $links[0]->source_id);
        $this->assertSame($link1->id, $links[1]->id);
        $this->assertSame($collection->id, $links[1]->source_id);
    }

    public function testListFromFollowedCollectionsSelectsHiddenLinkIfCollectionIsShared(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame($collection->id, $links[0]->source_id);
    }

    public function testListFromFollowedCollectionsSelectsFromPrivateCollectionIfShared(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame($collection->id, $links[0]->source_id);
    }

    public function testListFromFollowedCollectionsRespectsFromFollowedIfOldLinksButWithTimeFilterAll(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 8, 180);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        // time_filter 'all' will search links until 7 days before the date
        // when the user started to follow the collection
        /** @var int */
        $delta_followed_days = $this->fake('numberBetween', 0, 7);
        $followed_at = \Minz\Time::ago($days_ago - $delta_followed_days, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'created_at' => $followed_at,
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
            'time_filter' => 'all',
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame($collection->id, $links[0]->source_id);
    }

    public function testListFromFollowedCollectionsConsidersLinksFromFeeds(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $published_at1 = \Minz\Time::ago(3, 'days');
        $published_at2 = \Minz\Time::ago(1, 'days');
        $link1 = LinkFactory::create([
            'user_id' => null,
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => null,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => null,
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1->addCollection($collection, at: $published_at1);
        $link2->addCollection($collection, at: $published_at2);
        $this->user->follow($collection->id);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(2, count($links));
        $this->assertSame($link2->id, $links[0]->id);
        $this->assertSame($collection->id, $links[0]->source_id);
        $this->assertSame($link1->id, $links[1]->id);
        $this->assertSame($collection->id, $links[1]->source_id);
    }

    public function testListFromFollowedCollectionsDoesNotPickFromFollowedIfTooOld(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 8, 180);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfTooOldWithTimeFilterStrict(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $hours_ago = $this->fake('numberBetween', 25, 72);
        $published_at = \Minz\Time::ago($hours_ago, 'hours');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
            'time_filter' => 'strict',
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfLinkIsHidden(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfCollectionIsPrivate(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfUrlIsToReadLater(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        // This is only required while "read later" is handled via collections.
        $owned_link = $this->user->obtainLink($link);
        $owned_link->save();
        $this->user->markAsReadLater($owned_link);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfUrlIsRead(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        // This is only required while "read later" is handled via collections.
        $owned_link = $this->user->obtainLink($link);
        $owned_link->save();
        $this->user->markAsRead($owned_link);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfUrlIsDismissed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        // This is only required while "read later" is handled via collections.
        $owned_link = $this->user->obtainLink($link);
        $owned_link->save();
        $this->user->markAsDismissed($owned_link);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfLinkIsOwned(): void
    {
        // This is a very particular use case where the user got write access
        // to a collection while he was following it (or followed it
        // afterwards). This link should not appear in the news link.
        // We don't create a CollectionShare because it doesn't matter whether
        // the permission still exists or not.
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $link = LinkFactory::create([
            'user_id' => $this->user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testAnyFromFollowedCollectionsCanReturnTrue(): void
    {
        $published_at = \Minz\Time::ago(1, 'day');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $result = models\Link::anyFromFollowedCollections($this->user->id);

        $this->assertTrue($result);
    }

    public function testAnyFromFollowedCollectionsCanReturnFalse(): void
    {
        $published_at = \Minz\Time::ago(1, 'day');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'is_hidden' => true, // Note the link is hidden
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $published_at);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $result = models\Link::anyFromFollowedCollections($this->user->id);

        $this->assertFalse($result);
    }

    public function testAnyFromFollowedFeedsCanReturnTrue(): void
    {
        $published_at = \Minz\Time::ago(1, 'day');
        $link = LinkFactory::create([
            'user_id' => null,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => null,
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link->addCollection($collection, at: $published_at);
        $this->user->follow($collection->id);

        $result = models\Link::anyFromFollowedCollections($this->user->id);

        $this->assertTrue($result);
    }

    public function testAnyFromFollowedFeedsCanReturnFalse(): void
    {
        $published_at = \Minz\Time::ago(1, 'day');
        $link = LinkFactory::create([
            'user_id' => null,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => null,
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link->addCollection($collection, at: $published_at);
        $this->user->follow($collection->id);

        $result = models\Link::anyFromFollowedCollections($this->user->id);

        $this->assertFalse($result);
    }
}
