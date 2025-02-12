<?php

namespace App\models\dao\links;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at1,
            'collection_id' => $collection->id,
            'link_id' => $link1->id,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => $published_at2,
            'collection_id' => $collection->id,
            'link_id' => $link2->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(2, count($links));
        $this->assertSame($link2->id, $links[0]->id);
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
        $this->assertSame($link1->id, $links[1]->id);
        $this->assertSame('collection', $links[1]->source_news_type);
        $this->assertSame($collection->id, $links[1]->source_news_resource_id);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        FollowedCollectionFactory::create([
            'created_at' => $followed_at,
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
            'time_filter' => 'all',
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfUrlInBookmarks(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $bookmarks = $this->user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $owner_link = LinkFactory::create([
            'user_id' => $this->user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $owner_link->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfUrlInReadList(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $read_list = $this->user->readList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $owner_link = LinkFactory::create([
            'user_id' => $this->user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $read_list->id,
            'link_id' => $owner_link->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $links = models\Link::listFromFollowedCollections($this->user->id, max: 50);

        $this->assertSame(0, count($links));
    }

    public function testListFromFollowedCollectionsDoesNotSelectFromFollowedIfUrlInNeverList(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 7);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $never_list = $this->user->neverList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $owner_link = LinkFactory::create([
            'user_id' => $this->user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $never_list->id,
            'link_id' => $owner_link->id,
        ]);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
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
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $this->user->id,
            'collection_id' => $collection->id,
        ]);

        $result = models\Link::anyFromFollowedCollections($this->user->id);

        $this->assertFalse($result);
    }
}
