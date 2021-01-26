<?php

namespace flusio;

class NewsLinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersNewsLinksCorrectly()
    {
        $user = $this->login();
        $title_news = $this->fake('sentence');
        $title_not_news_1 = $this->fake('sentence');
        $title_not_news_2 = $this->fake('sentence');
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_news,
            'is_read' => 0,
            'is_removed' => 0,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_not_news_1,
            'is_read' => 0,
            'is_removed' => 1,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_not_news_2,
            'is_read' => 1,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString($title_news, $response_output);
        $this->assertStringNotContainsString($title_not_news_1, $response_output);
        $this->assertStringNotContainsString($title_not_news_2, $response_output);
    }

    public function testIndexShowsIfViaBookmarks()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
            'is_read' => 0,
            'is_removed' => 0,
            'via_type' => 'bookmarks',
            'via_link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('via your <strong>bookmarks</strong>', $response_output);
    }

    public function testIndexRendersIfViaFollowedCollections()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $collection_name = $this->fake('word');
        $username = $this->fake('username');
        $other_user_id = $this->create('user', [
            'username' => $username,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
            'is_public' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'is_read' => 0,
            'is_removed' => 0,
            'url' => $url,
            'via_type' => 'followed',
            'via_link_id' => $link_id,
            'via_collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString(
            "via <strong>{$collection_name}</strong> by {$username}",
            $response_output
        );
    }

    public function testIndexRendersIfViaTopics()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $username = $this->fake('username');
        $other_user_id = $this->create('user', [
            'username' => $username,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
            'is_public' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'is_public' => 1,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $topic_id = $this->create('topic');
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);
        $this->create('user_to_topic', [
            'user_id' => $user->id,
            'topic_id' => $topic_id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'is_read' => 0,
            'is_removed' => 0,
            'url' => $url,
            'via_type' => 'topics',
            'via_link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString(
            "via your <strong>points of interest</strong>, added by {$username}",
            $response_output
        );
    }

    public function testIndexRendersTipsIfNoNewsFlash()
    {
        $user = $this->login();
        utils\Flash::set('no_news', true);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('We found no relevant news for you, what can you do?', $response_output);
    }

    public function testIndexHidesAddToCollectionsIfUserHasNoCollections()
    {
        $user = $this->login();
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $this->fake('sentence'),
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringNotContainsString('Add to collections', $response_output);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
    }

    public function testPreferencesRendersCorrectly()
    {
        $preferences = models\NewsPreferences::init(666, true, true, true);
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('get', '/news/preferences');

        $this->assertResponse($response, 200, 666);
        $this->assertPointer($response, 'news_links/preferences.phtml');
    }

    public function testPreferencesRedirectsIfNotConnected()
    {
        $preferences = models\NewsPreferences::init(666, true, true, true);
        $user_id = $this->create('user', [
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('get', '/news/preferences');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews%2Fpreferences');
    }

    public function testUpdatePreferencesSavesUserNewsPreferencesAndRedirects()
    {
        $user_dao = new models\dao\User();
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => $user->csrf,
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 302, '/news');
        $user = new models\User($user_dao->find($user->id));
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($new_duration, $news_preferences->duration);
        $this->assertSame($new_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($new_from_followed, $news_preferences->from_followed);
        $this->assertSame($new_from_topics, $news_preferences->from_topics);
    }

    public function testUpdatePreferencesRedirectsIfNotConnected()
    {
        $user_dao = new models\dao\User();
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => 'a token',
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews%2Fpreferences');
        $user = new models\User($user_dao->find($user_id));
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testUpdatePreferencesFailsIfCsrfIsInvalid()
    {
        $user_dao = new models\dao\User();
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => 'not the token',
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $user = new models\User($user_dao->find($user->id));
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testUpdatePreferencesFailsIfDurationIsInvalid()
    {
        $user_dao = new models\dao\User();
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('randomNumber') * -1;
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => $user->csrf,
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 400, "The duration must be between {$min_duration} and {$max_duration}");
        $user = new models\User($user_dao->find($user->id));
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testUpdatePreferencesFailsIfNoFromIsSelected()
    {
        $user_dao = new models\dao\User();
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = false;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = false;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = false;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => $user->csrf,
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 400, 'You must select at least one option');
        $user = new models\User($user_dao->find($user->id));
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testFillSelectsLinksFromBookmarksAndRedirects()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
        $this->assertSame('bookmarks', $news_link['via_type']);
        $this->assertSame($link_id, $news_link['via_link_id']);
    }

    public function testFillSelectsLinksFromFollowedCollections()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
            'reading_time' => 10,
            'is_public' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
        $this->assertSame($user->id, $news_link['user_id']);
        $this->assertSame('followed', $news_link['via_type']);
        $this->assertSame($collection_id, $news_link['via_collection_id']);
        $this->assertSame($link_id, $news_link['via_link_id']);
    }

    public function testFillSelectsLinksFromTopics()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
            'reading_time' => 10,
            'is_public' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $topic_id = $this->create('topic');
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);
        $this->create('user_to_topic', [
            'user_id' => $user->id,
            'topic_id' => $topic_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
        $this->assertSame($user->id, $news_link['user_id']);
        $this->assertSame('topics', $news_link['via_type']);
        $this->assertSame($collection_id, $news_link['via_collection_id']);
        $this->assertSame($link_id, $news_link['via_link_id']);
    }

    public function testFillSelectsLinksUpToAbout1Hour()
    {
        $news_link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url_1 = $this->fake('url');
        $link_url_2 = $this->fake('url');
        $link_url_3 = $this->fake('url');
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_1,
            'reading_time' => 15,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_2,
            'reading_time' => 25,
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_3,
            'reading_time' => 20,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_1,
            'collection_id' => $bookmarks_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_2,
            'collection_id' => $bookmarks_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_3,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $news_link_1 = $news_link_dao->findBy(['url' => $link_url_1]);
        $news_link_2 = $news_link_dao->findBy(['url' => $link_url_2]);
        $news_link_3 = $news_link_dao->findBy(['url' => $link_url_3]);
        $this->assertNotNull($news_link_1);
        $this->assertNotNull($news_link_2);
        $this->assertNotNull($news_link_3);
    }

    public function testFillDoesNotSelectTooLongLinks()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => 75,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testFillDoesNotSelectNotBookmarkedLinks()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testFillSetsFlashNoNewsIfNoSuggestions()
    {
        $user = $this->login();

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertFlash('no_news', true);
    }

    public function testFillRedirectsIfNotConnected()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testFillFailsIfCsrfIsInvalid()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }
}
