<?php

namespace flusio\services;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class NewsPickerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    private models\User $user;
    private models\User $other_user;

    /**
     * @before
     */
    public function setUsers(): void
    {
        $this->user = UserFactory::create();
        $this->other_user = UserFactory::create();
    }

    public function testPickSelectsFromBookmarks(): void
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
        ]);
        $link = LinkFactory::create([
            'user_id' => $this->user->id,
        ]);
        $bookmarks = $this->user->bookmarks();
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $link->id,
        ]);

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame('bookmarks', $links[0]->source_news_type);
    }

    public function testPickSelectsFromFollowed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
    }

    public function testPickSelectsHiddenLinkIfCollectionIsShared(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
    }

    public function testPickSelectsFromPrivateCollectionIfShared(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
    }

    public function testPickRespectsMinDuration(): void
    {
        /** @var int */
        $duration = $this->fake('numberBetween', 0, 9000);
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
            'min_duration' => $duration,
        ]);
        $link_1 = LinkFactory::create([
            'user_id' => $this->user->id,
            'reading_time' => $duration,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $this->user->id,
            'reading_time' => $duration - 1,
        ]);
        $bookmarks = $this->user->bookmarks();
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $link_1->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $link_2->id,
        ]);

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testPickRespectsMaxDuration(): void
    {
        /** @var int */
        $duration = $this->fake('numberBetween', 0, 9000);
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
            'max_duration' => $duration,
        ]);
        $link_1 = LinkFactory::create([
            'user_id' => $this->user->id,
            'reading_time' => $duration,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $this->user->id,
            'reading_time' => $duration - 1,
        ]);
        $bookmarks = $this->user->bookmarks();
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $link_1->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $link_2->id,
        ]);

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testPickRespectsFromFollowedIfOldLinksButWithTimeFilterAll(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 9999);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        // time_filter 'all' will search links until 3 days before the date
        // when the user started to follow the collection
        $delta_followed_days = $this->fake('numberBetween', 0, 3);
        $followed_at = \Minz\Time::ago($days_ago - $delta_followed_days, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
        $this->assertSame('collection', $links[0]->source_news_type);
        $this->assertSame($collection->id, $links[0]->source_news_resource_id);
    }

    public function testPickDoesNotSelectFromBookmarksIfNotSelected(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $link = LinkFactory::create([
            'user_id' => $this->user->id,
        ]);
        $bookmarks = $this->user->bookmarks();
        LinkToCollectionFactory::create([
            'created_at' => $published_at,
            'collection_id' => $bookmarks->id,
            'link_id' => $link->id,
        ]);

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfNotSelected(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotPickFromFollowedIfTooOld(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 4, 9999);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfTooOldWithTimeFilterStrict(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $hours_ago = $this->fake('numberBetween', 25, 72);
        $published_at = \Minz\Time::ago($hours_ago, 'hours');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfLinkIsHidden(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfCollectionIsPrivate(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfUrlInBookmarks(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfUrlInReadList(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfUrlInNeverList(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }

    public function testPickDoesNotSelectFromFollowedIfLinkIsOwned(): void
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
        $days_ago = $this->fake('numberBetween', 0, 3);
        $published_at = \Minz\Time::ago($days_ago, 'days');
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
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

        $links = $news_picker->pick();

        $this->assertSame(0, count($links));
    }
}
