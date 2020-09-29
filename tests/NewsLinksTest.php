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
        $title_not_news = $this->fake('sentence');
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_news,
            'is_hidden' => 0,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_not_news,
            'is_hidden' => 1,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString($title_news, $response_output);
        $this->assertStringNotContainsString($title_not_news, $response_output);
    }

    public function testIndexShowsNumberOfCollections()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('In 1 collection', $response_output);
    }

    public function testIndexShowsIfInBookmarks()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('In your bookmarks', $response_output);
        $this->assertStringContainsString('and 1 collection', $response_output);
    }

    public function testIndexRendersIfInFollowedCollections()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $this->create('news_link', [
            'user_id' => $user->id,
            'is_hidden' => 0,
            'url' => $url,
        ]);

        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
            'is_public' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
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

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString('added this link', $response_output);
    }

    public function testIndexRendersTipsIfNoNewsFlash()
    {
        $user = $this->login();
        utils\Flash::set('no_news', true);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('We found no relevant news for you, what can you do?', $response_output);
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

    public function testAddingRendersCorrectly()
    {
        $user = $this->login();
        $title = $this->fake('sentence');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'news_links/adding.phtml');
    }

    public function testAddingAdaptsSubmitButtonIfTheLinkIsAlreadyPartOfCollections()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 200, 'Save and mark as read');
    }

    public function testAddingRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $title = $this->fake('sentence');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fnews%2F{$news_link_id}%2Fadd");
    }

    public function testAddingFailsIfLinkIsNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $title = $this->fake('sentence');
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 404);
    }

    public function testAddCreatesALinkAndRedirects()
    {
        $user = $this->login();
        $link_dao = new models\dao\Link();
        $news_link_dao = new models\dao\NewsLink();
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $this->assertSame(0, $link_dao->count());

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertSame(1, $link_dao->count());

        $this->assertResponse($response, 302, '/news');
        $link = new models\Link($link_dao->listAll()[0]);
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $message = $link->messages()[0];
        $db_link_to_collection = $links_to_collections_dao->listAll()[0];
        $this->assertTrue($news_link->is_hidden);
        $this->assertSame($user->id, $link->user_id);
        $this->assertSame($news_link->title, $link->title);
        $this->assertSame($news_link->url, $link->url);
        $this->assertTrue($link->is_public);
        $this->assertSame($comment, $message->content);
        $this->assertSame($link->id, $db_link_to_collection['link_id']);
        $this->assertSame($collection_id, $db_link_to_collection['collection_id']);
    }

    public function testAddUpdatesExistingLinks()
    {
        $user = $this->login();
        $link_dao = new models\dao\Link();
        $news_link_dao = new models\dao\NewsLink();
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $url = $this->fake('url');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'is_public' => 0,
        ]);
        $old_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $new_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $old_collection_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$new_collection_id];

        $this->assertSame(1, $link_dao->count());

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertSame(1, $link_dao->count());

        $this->assertResponse($response, 302, '/news');
        $link = new models\Link($link_dao->find($link_id));
        $this->assertTrue($link->is_public);
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
        $new_db_link_to_collection = $links_to_collections_dao->listAll()[0];
        $this->assertSame($link_id, $new_db_link_to_collection['link_id']);
        $this->assertSame($new_collection_id, $new_db_link_to_collection['collection_id']);
    }

    public function testAddRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_dao = new models\dao\Link();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => 'a token',
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fnews%2F{$news_link_id}%2Fadd");
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfUserDoesNotOwnTheNewsLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_dao = new models\dao\Link();
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link_dao = new models\dao\Link();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => 'not the token',
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfCollectionIdsIsEmpty()
    {
        $user = $this->login();
        $link_dao = new models\dao\Link();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 400, 'The link must be associated to a collection.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfCollectionIdsContainsNotOwnedId()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_dao = new models\dao\Link();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 400, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testReadLaterRemovesLinkFromNewsAndAddsToBookmarksAndRedirects()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertFalse($exists, 'The news should no longer exist.');
        $this->assertNotNull($db_link_to_collection, 'The link should be in bookmarks.');
    }

    public function testReadLaterCreatesTheLinkIfItDoesNotExistForCurrentUser()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertNotNull($db_link, 'The link should exist.');
    }

    public function testReadLaterJustRemovesFromNewsIfAlreadyBookmarked()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link_to_collection = $links_to_collections_dao->find($link_to_collection_id);
        $this->assertFalse($exists, 'The news should no longer exist.');
        $this->assertNotNull($db_link_to_collection, 'The link should still be in bookmarks.');
    }

    public function testReadLaterRedirectsToLoginIfNotConnected()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfCsrfIsInvalid()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfLinkDoesNotExist()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', '/news/-1/read-later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfUserDoesNotOwnTheLink()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testHideHidesLinkFromNewsAndRedirects()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/hide", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $this->assertTrue($news_link->is_hidden, 'The news link should be hidden.');
    }

    public function testHideRemovesFromBookmarksIfCorrespondingUrlInLinks()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/hide", [
            'csrf' => $user->csrf,
        ]);

        $exists_in_bookmarks = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($exists_in_bookmarks, 'The link should no longer be in bookmarks.');
    }

    public function testHideRedirectsToLoginIfNotConnected()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $this->fake('url'),
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/hide", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $this->assertFalse($news_link->is_hidden, 'The news link should not be hidden.');
    }

    public function testHideFailsIfCsrfIsInvalid()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/hide", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $this->assertFalse($news_link->is_hidden, 'The news link should not be hidden.');
    }

    public function testHideFailsIfUserDoesNotOwnTheLink()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $this->fake('url'),
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/hide", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $this->assertFalse($news_link->is_hidden, 'The news link should not be hidden.');
    }
}
