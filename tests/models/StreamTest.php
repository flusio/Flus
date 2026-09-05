<?php

namespace App\models;

use tests\factories\CollectionFactory;
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

    public function testHasUnreadLinksReturnsTrueIfASourceHasUnreadLinks(): void
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

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertTrue($has_unread_links);
    }

    public function testHasUnreadLinksReturnsFalseIfStreamHasNoSource(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertFalse($has_unread_links);
    }

    public function testHasUnreadLinksReturnsFalseIfStreamHasNoLink(): void
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

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertFalse($has_unread_links);
    }

    public function testHasUnreadLinksReturnsFalseIfLinksAreNotUnread(): void
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

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertFalse($has_unread_links);
    }

    public function testHasUnreadLinksReturnsFalseIfLinkIsPublishedBeforeThePastWeek(): void
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

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertFalse($has_unread_links);
    }

    public function testHasUnreadLinksReturnsFalseIfLinkIsHidden(): void
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

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertFalse($has_unread_links);
    }

    public function testHasUnreadLinksReturnsFalseIfSourceIsNotVisible(): void
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

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertFalse($has_unread_links);
    }

    public function testHasUnreadLinksReturnsTrueIfSourceIsShared(): void
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
        $source->shareWith($user, 'read');
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        $has_unread_links = $stream->hasUnreadLinks($user);

        $this->assertTrue($has_unread_links);
    }

    public function testHasUnreadLinksConsidersTheVisibilityOfTheGivenUser(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $third_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        // The source is shared with the owner of the stream, but not with
        // other_user: the same stream has unread links for the first and not
        // for the second.
        $source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $third_user->id,
            'is_public' => false,
        ]);
        $source->shareWith($user, 'read');
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        $has_unread_links = $stream->hasUnreadLinks($user);
        $other_has_unread_links = $stream->hasUnreadLinks($other_user);

        $this->assertTrue($has_unread_links);
        $this->assertFalse($other_has_unread_links);
    }

    public function testDisplaysUnreadInSidenavReturnsTrueIfStreamHasUnreadLinks(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'display_unread_in_sidenav' => true,
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

        $displays_unread_in_sidenav = $stream->displaysUnreadInSidenav($user);

        $this->assertTrue($displays_unread_in_sidenav);
    }

    public function testDisplaysUnreadInSidenavReturnsFalseIfStreamDoesNotDisplayUnreadInSidenav(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'display_unread_in_sidenav' => false,
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

        $has_unread_links = $stream->hasUnreadLinks($user);
        $displays_unread_in_sidenav = $stream->displaysUnreadInSidenav($user);

        $this->assertTrue($has_unread_links);
        $this->assertFalse($displays_unread_in_sidenav);
    }
}
