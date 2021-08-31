<?php

namespace flusio\services;

use flusio\models;

class NewsPickerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

    private $user;
    private $other_user;

    /**
     * @before
     */
    public function setUsers()
    {
        $user_id = $this->create('user');
        $this->user = models\User::find($user_id);

        $user_id = $this->create('user');
        $this->other_user = models\User::find($user_id);
    }

    public function testPickSelectsFromBookmarks()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->user->id,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id, $db_links[0]['id']);
        $this->assertSame('bookmarks', $db_links[0]['news_via_type']);
    }

    public function testPickSelectsFromFollowed()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id, $db_links[0]['id']);
        $this->assertSame('followed', $db_links[0]['news_via_type']);
        $this->assertSame($collection_id, $db_links[0]['news_via_collection_id']);
    }

    public function testPickRespectsMinDuration()
    {
        $duration = $this->fake('numberBetween', 0, 9000);
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
            'min_duration' => $duration,
        ]);
        $link_id_1 = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => $duration,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => $duration - 1,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_2,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id_1, $db_links[0]['id']);
    }

    public function testPickRespectsMaxDuration()
    {
        $duration = $this->fake('numberBetween', 0, 9000);
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
            'max_duration' => $duration,
        ]);
        $link_id_1 = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => $duration,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => $duration - 1,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_2,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id_2, $db_links[0]['id']);
    }

    public function testPickRespectsUntil()
    {
        $until = $this->fake('datetime');
        $created_at_1 = clone $until;
        $created_at_1->modify('-1 second');
        $created_at_2 = clone $until;
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
            'until' => $until,
        ]);
        $link_id_1 = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 0,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'created_at' => $created_at_1->format(\Minz\Model::DATETIME_FORMAT),
            'collection_id' => $collection_id,
            'link_id' => $link_id_1,
        ]);
        $this->create('link_to_collection', [
            'created_at' => $created_at_2->format(\Minz\Model::DATETIME_FORMAT),
            'collection_id' => $collection_id,
            'link_id' => $link_id_2,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id_2, $db_links[0]['id']);
    }

    public function testPickDoesNotSelectFromBookmarksIfNotSelected()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->user->id,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromFollowedIfNotSelected()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromFollowedIfLinkIsHidden()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromFollowedIfCollectionIsPrivate()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromFollowedIfUrlInNewsLink()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);
        $this->create('news_link', [
            'user_id' => $this->user->id,
            'url' => $url,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromFollowedIfInBookmarks()
    {
        $news_picker = new NewsPicker($this->user, [
            'from' => 'followed',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }
}
