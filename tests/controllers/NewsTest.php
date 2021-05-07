<?php

namespace flusio\controllers;

use flusio\models;
use flusio\utils;

class NewsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersNewsLinksCorrectly()
    {
        $user = $this->login();
        $title_news = $this->fakeUnique('sentence');
        $title_not_news_1 = $this->fakeUnique('sentence');
        $title_not_news_2 = $this->fakeUnique('sentence');
        $title_not_news_3 = $this->fakeUnique('sentence');
        $link_news_id = $this->create('link', [
            'title' => $title_news,
            'user_id' => $user->id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'link_id' => $link_news_id,
            'read_at' => null,
            'removed_at' => null,
        ]);
        $link_not_news_id_1 = $this->create('link', [
            'title' => $title_not_news_1,
            'user_id' => $user->id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'link_id' => $link_not_news_id_1,
            'read_at' => null,
            'removed_at' => $this->fake('iso8601'),
        ]);
        $link_not_news_id_2 = $this->create('link', [
            'title' => $title_not_news_2,
            'user_id' => $user->id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'link_id' => $link_not_news_id_2,
            'read_at' => $this->fake('iso8601'),
            'removed_at' => null,
        ]);
        $link_not_news_id_3 = $this->create('link', [
            'title' => $title_not_news_3,
            'user_id' => $this->create('user'),
            'is_hidden' => 1,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'link_id' => $link_not_news_id_3,
            'read_at' => null,
            'removed_at' => null,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString($title_news, $response_output);
        $this->assertStringNotContainsString($title_not_news_1, $response_output);
        $this->assertStringNotContainsString($title_not_news_2, $response_output);
        $this->assertStringNotContainsString($title_not_news_3, $response_output);
    }

    public function testShowShowsIfViaBookmarks()
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
            'read_at' => null,
            'removed_at' => null,
            'link_id' => $link_id,
            'via_type' => 'bookmarks',
        ]);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('via your <strong>bookmarks</strong>', $response_output);
    }

    public function testShowRendersIfViaFollowedCollections()
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
            'is_hidden' => 0,
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
            'read_at' => null,
            'removed_at' => null,
            'url' => $url,
            'link_id' => $link_id,
            'via_type' => 'followed',
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

    public function testShowRendersTipsIfNoNewsFlash()
    {
        $user = $this->login();
        utils\Flash::set('no_news', true);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringContainsString('We haven’t found any relevant links for the moment.', $response_output);
    }

    public function testShowHidesAddToCollectionsIfUserHasNoCollections()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'link_id' => $link_id,
            'read_at' => null,
            'removed_at' => null,
        ]);

        $response = $this->appRun('get', '/news');

        $response_output = $response->render();
        $this->assertStringNotContainsString('Add to collections', $response_output);
    }

    public function testShowRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
    }

    public function testCreateSelectsLinksFromBookmarksIfTypeIsShort()
    {
        $user = $this->login();
        $link_url = $this->fake('url');
        $duration = $this->fake('numberBetween', 0, 9);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => $duration,
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
            'type' => 'short',
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = models\NewsLink::findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
        $this->assertSame('bookmarks', $news_link->via_type);
        $this->assertSame($link_id, $news_link->link_id);
    }

    public function testCreateSelectsLinksFromBookmarksIfTypeIsLong()
    {
        $user = $this->login();
        $link_url = $this->fake('url');
        $duration = $this->fake('numberBetween', 10, 9000);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => $duration,
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
            'type' => 'long',
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = models\NewsLink::findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
        $this->assertSame('bookmarks', $news_link->via_type);
        $this->assertSame($link_id, $news_link->link_id);
    }

    public function testCreateSelectsLinksFromFollowedIfTypeIsNewsfeed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'url' => $link_url,
            'is_hidden' => 0,
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
            'type' => 'newsfeed',
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = models\NewsLink::findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
        $this->assertSame($user->id, $news_link->user_id);
        $this->assertSame('followed', $news_link->via_type);
        $this->assertSame($collection_id, $news_link->via_collection_id);
        $this->assertSame($link_id, $news_link->link_id);
    }

    public function testCreateSetsFlashNoNewsIfNoSuggestions()
    {
        $user = $this->login();

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertFlash('no_news', true);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $duration = $this->fake('numberBetween', 0, 9);
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
            'reading_time' => $duration,
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
            'type' => 'short',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $news_link = models\NewsLink::findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link_url = $this->fake('url');
        $duration = $this->fake('numberBetween', 0, 9);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => $duration,
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
            'type' => 'short',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $news_link = models\NewsLink::findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }
}
