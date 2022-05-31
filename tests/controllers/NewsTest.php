<?php

namespace flusio\controllers;

use flusio\models;
use flusio\utils;

class NewsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersNewsLinksCorrectly()
    {
        $user = $this->login();
        $news = $user->news();
        $title = $this->fakeUnique('sentence');
        $link_id = $this->create('link', [
            'title' => $title,
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'news/index.phtml');
        $this->assertResponseContains($response, $title);
    }

    public function testIndexRendersIfViaBookmarks()
    {
        $user = $this->login();
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'via_type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponseCode($response, 200);
        $bookmarks_url = \Minz\Url::for('bookmarks');
        $bookmarks_anchor = "<a class=\"anchor--hidden\" href=\"{$bookmarks_url}\">bookmarks</a>";
        $this->assertResponseContains($response, "via your <strong>{$bookmarks_anchor}</strong>");
    }

    public function testIndexRendersIfViaFollowedCollections()
    {
        $user = $this->login();
        $username = $this->fake('username');
        $other_user_id = $this->create('user', [
            'username' => $username,
        ]);
        $collection_name = $this->fake('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'name' => $collection_name,
        ]);
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'via_type' => 'collection',
            'via_resource_id' => $collection_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponseCode($response, 200);
        $collection_url = \Minz\Url::for('collection', ['id' => $collection_id]);
        $collection_anchor = "<a class=\"anchor--hidden\" href=\"{$collection_url}\">{$collection_name}</a>";
        $profile_url = \Minz\Url::for('profile', ['id' => $other_user_id]);
        $profile_anchor = "<a class=\"anchor--hidden\" href=\"{$profile_url}\">{$username}</a>";
        $this->assertResponseContains($response, "via <strong>{$collection_anchor}</strong> by {$profile_anchor}");
    }

    public function testIndexRendersTipsIfNoNewsFlash()
    {
        $user = $this->login();
        $news = $user->news();
        $title = $this->fakeUnique('sentence');
        $link_id = $this->create('link', [
            'title' => $title,
            'user_id' => $user->id,
        ]);
        utils\Flash::set('no_news', true);

        $response = $this->appRun('get', '/news');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'There are no relevant links to suggest at this time.');
    }

    public function testIndexHidesAddToCollectionsIfUserHasNoCollections()
    {
        $user = $this->login();
        $news = $user->news();
        $title = $this->fakeUnique('sentence');
        $link_id = $this->create('link', [
            'title' => $title,
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, 'Add to collections');
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $news = $user->news();
        $title = $this->fakeUnique('sentence');
        $link_id = $this->create('link', [
            'title' => $title,
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
    }

    public function testCreateSelectsLinksFromBookmarksIfTypeIsShort()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $duration = $this->fake('numberBetween', 0, 9);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $link = models\Link::find($link_id);
        $this->assertSame('bookmarks', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateSelectsLinksFromBookmarksIfTypeIsLong()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $duration = $this->fake('numberBetween', 10, 9000);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
            'type' => 'long',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $link = models\Link::find($link_id);
        $this->assertSame('bookmarks', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateSelectsLinksFromFollowedIfTypeIsNewsfeed()
    {
        $user = $this->login();
        $news = $user->news();
        $other_user_id = $this->create('user');
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
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

        $this->assertResponseCode($response, 302, '/news');
        $news_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $this->assertNotNull($news_link);
        $this->assertSame('collection', $news_link->via_type);
        $this->assertSame($collection_id, $news_link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $news_link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateMemorizesViaIfAleardySet()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = $this->fake('url');
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $duration = $this->fake('numberBetween', 0, 9);
        $via_link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
            'url' => $url,
        ]);
        $via_collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $via_link_id,
            'collection_id' => $via_collection_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'reading_time' => $duration,
            'url' => $url,
            'via_type' => 'collection',
            'via_resource_id' => $via_collection_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $link = models\Link::find($link_id);
        $this->assertSame('collection', $link->via_type);
        $this->assertSame($via_collection_id, $link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateDoesNotDuplicatesLink()
    {
        $user = $this->login();
        $news = $user->news();
        $other_user_id = $this->create('user');
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_url = $this->fake('url');
        $owned_link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
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

        $this->assertResponseCode($response, 302, '/news');
        $news_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $this->assertSame($owned_link_id, $news_link->id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $owned_link_id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
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
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $duration = $this->fake('numberBetween', 0, 9);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $link = models\Link::find($link_id);
        $this->assertSame('', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $this->assertNull($link_to_news);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $duration = $this->fake('numberBetween', 0, 9);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => 'not the token',
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $link = models\Link::find($link_id);
        $this->assertSame('', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $this->assertNull($link_to_news);
    }
}
