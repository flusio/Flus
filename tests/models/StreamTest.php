<?php

namespace App\models;

use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class StreamTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testListByUserReturnsTheStreamsOfTheUser(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertSame($stream->id, $streams[0]->id);
    }

    public function testListByUserComputesHasUnreadLinks(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertTrue($streams[0]->has_unread_links);
    }

    public function testListByUserDoesNotSetHasUnreadLinksIfStreamHasNoSource(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertFalse($streams[0]->has_unread_links);
    }

    public function testListByUserDoesNotSetHasUnreadLinksIfStreamHasNoLink(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertFalse($streams[0]->has_unread_links);
    }

    public function testListByUserDoesNotSetHasUnreadLinksIfLinksAreNotUnread(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $read_link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $read_later_link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $dismissed_link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$read_link, $read_later_link, $dismissed_link], at: \Minz\Time::now());
        $stream->addSource($source);
        $user->markAsRead($read_link);
        $user->markAsReadLater($read_later_link);
        $user->markAsDismissed($dismissed_link);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertFalse($streams[0]->has_unread_links);
    }

    public function testListByUserComputesHasUnreadLinksIfLinkIsPublishedDuringThePastWeek(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::ago(3, 'days'));
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertTrue($streams[0]->has_unread_links);
    }

    public function testListByUserDoesNotSetHasUnreadLinksIfLinkIsPublishedBeforeThePastWeek(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::ago(2, 'weeks'));
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertFalse($streams[0]->has_unread_links);
    }

    public function testListByUserDoesNotSetHasUnreadLinksIfLinkIsHidden(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => true,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertFalse($streams[0]->has_unread_links);
    }

    public function testListByUserDoesNotSetHasUnreadLinksIfSourceIsNotVisible(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertFalse($streams[0]->has_unread_links);
    }

    public function testListByUserComputesHasUnreadLinksIfSourceIsShared(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $source->id,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        $streams = Stream::listByUser($user);

        $this->assertSame(1, count($streams));
        $this->assertTrue($streams[0]->has_unread_links);
    }
}
