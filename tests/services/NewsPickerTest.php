<?php

namespace flusio\services;

use flusio\models;

class NewsPickerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

    private $duration;
    private $user;
    private $other_user;

    /**
     * @before
     */
    public function setUsers()
    {
        $user_dao = new models\dao\User();

        $this->duration = $this->fake('numberBetween', 30, 60);
        $news_preferences = models\NewsPreferences::init($this->duration, true, true, true);

        $user_id = $this->create('user', [
            'news_preferences' => $news_preferences->toJson(),
        ]);
        $db_user = $user_dao->find($user_id);
        $this->user = new models\User($db_user);

        $user_id = $this->create('user', [
            'news_preferences' => $news_preferences->toJson(),
        ]);
        $db_user = $user_dao->find($user_id);
        $this->other_user = new models\User($db_user);
    }

    public function testPickSelectsFromBookmarks()
    {
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => 20,
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
    }

    public function testPickSelectsFromFollowed()
    {
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 20,
            'is_public' => 1,
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
    }

    public function testPickSelectsFromTopics()
    {
        $news_picker = new NewsPicker($this->user);
        $topic_id1 = $this->create('topic');
        $topic_id2 = $this->create('topic');
        // make the user interested by topic1
        $this->create('user_to_topic', [
            'user_id' => $this->user->id,
            'topic_id' => $topic_id1,
        ]);
        // create a link to a collection associated to topic1
        $link_id1 = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 10,
            'is_public' => 1,
        ]);
        $collection_id1 = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id1,
            'link_id' => $link_id1,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id1,
            'topic_id' => $topic_id1,
        ]);
        // create another link to a collection associated to topic2
        $link_id2 = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 10,
            'is_public' => 1,
        ]);
        $collection_id2 = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id2,
            'link_id' => $link_id2,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id2,
            'topic_id' => $topic_id2,
        ]);

        $db_links = $news_picker->pick();

        // because the user is only interested by topic1, we get the link
        // associated to topic1
        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id1, $db_links[0]['id']);
    }

    public function testPickSelectsForAtLeastMinimumDurationOfReading()
    {
        $news_picker = new NewsPicker($this->user);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        for ($i = 0; $i < 50; $i++) {
            $link_id = $this->create('link', [
                'user_id' => $this->user->id,
                'reading_time' => $this->fake('numberBetween', 2, 5),
            ]);
            $this->create('link_to_collection', [
                'collection_id' => $bookmarks_id,
                'link_id' => $link_id,
            ]);
        }

        $db_links = $news_picker->pick();

        $reading_times = array_column($db_links, 'reading_time');
        $total_reading_time = array_sum($reading_times);
        $this->assertGreaterThanOrEqual($this->duration * NewsPicker::MIN_DURATION_RATIO, $total_reading_time);
        $this->assertLessThanOrEqual($this->duration * NewsPicker::MAX_DURATION_RATIO, $total_reading_time);
    }

    public function testPickOrdersByNewsValue()
    {
        $news_picker = new NewsPicker($this->user);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $link_id = $this->create('link', [
                'user_id' => $this->user->id,
                'reading_time' => $this->fake('numberBetween', 1, 5),
            ]);
            $this->create('link_to_collection', [
                'collection_id' => $bookmarks_id,
                'link_id' => $link_id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $link_id = $this->create('link', [
                'user_id' => $this->other_user->id,
                'reading_time' => $this->fake('numberBetween', 1, 5),
                'is_public' => 1,
            ]);
            $this->create('link_to_collection', [
                'collection_id' => $collection_id,
                'link_id' => $link_id,
            ]);
        }

        $db_links = $news_picker->pick();

        $first_db_link = array_shift($db_links);
        $previous_value = $first_db_link['news_value'];
        foreach ($db_links as $db_link) {
            $current_value = $db_link['news_value'];
            $this->assertLessThanOrEqual($previous_value, $current_value);
            $previous_value = $current_value;
        }
    }

    public function testPickRemovesDuplicatedUrls()
    {
        $news_picker = new NewsPicker($this->user);
        $url = $this->fake('url');

        // initialize a link in the user bookmarks collection
        $link_id_1 = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => 20,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_1,
        ]);

        // initialize a link with the same URL in a followed collection
        $link_id_2 = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 20,
            'is_public' => 1,
            'url' => $url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->other_user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id_2,
        ]);
        $this->create('followed_collection', [
            'user_id' => $this->user->id,
            'collection_id' => $collection_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(1, count($db_links));
    }

    public function testPickDoesNotSelectFromBookmarksIfDisabled()
    {
        $news_preferences = models\NewsPreferences::init($this->duration, false, true, true);
        $this->user->news_preferences = $news_preferences->toJson();
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => 20,
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

    public function testPickDoesNotSelectFromFollowedIfDisabled()
    {
        $news_preferences = models\NewsPreferences::init($this->duration, true, false, true);
        $this->user->news_preferences = $news_preferences->toJson();
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 20,
            'is_public' => 1,
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

    public function testPickDoesNotSelectFromTopicsIfDisabled()
    {
        $news_preferences = models\NewsPreferences::init($this->duration, true, true, false);
        $this->user->news_preferences = $news_preferences->toJson();
        $news_picker = new NewsPicker($this->user);
        $topic_id = $this->create('topic');
        // make the user interested by topic1
        $this->create('user_to_topic', [
            'user_id' => $this->user->id,
            'topic_id' => $topic_id,
        ]);
        // create a link to a collection associated to topic1
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 10,
            'is_public' => 1,
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
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromUnfollowedDefaultCollections()
    {
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => 20,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $db_links = $news_picker->pick();

        $this->assertSame(0, count($db_links));
    }

    public function testPickDoesNotSelectFromFollowedIfLinkIsNotPublic()
    {
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 20,
            'is_public' => 0,
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

    public function testPickDoesNotSelectFromFollowedIfCollectionIsNotPublic()
    {
        $news_picker = new NewsPicker($this->user);
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 20,
            'is_public' => 1,
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

    public function testPickDoesNotSelectFromFollowedIfLinkUrlIsAlreadyInUserNews()
    {
        $news_picker = new NewsPicker($this->user);
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $this->other_user->id,
            'reading_time' => 20,
            'is_public' => 1,
            'url' => $url,
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

    public function testPickDoesNotSelectFromTopicsIfCollectionIsOwned()
    {
        $news_picker = new NewsPicker($this->user);
        $topic_id = $this->create('topic');
        // make the user interested by topic
        $this->create('user_to_topic', [
            'user_id' => $this->user->id,
            'topic_id' => $topic_id,
        ]);
        // create a link in a collection associated to topic
        $link_id = $this->create('link', [
            'user_id' => $this->user->id,
            'reading_time' => 10,
            'is_public' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $this->user->id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);

        $db_links = $news_picker->pick();

        // because the user owns the collection, we don't get the link
        // associated to the topic
        $this->assertSame(0, count($db_links));
    }
}
