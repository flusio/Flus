<?php

namespace flusio\controllers;

use flusio\models;
use flusio\utils;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class NewsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersNewsLinksCorrectly(): void
    {
        $user = $this->login();
        $news = $user->news();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'news/index.phtml');
        $this->assertResponseContains($response, $title);
    }

    public function testIndexRendersIfViaBookmarks(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'via_type' => 'bookmarks',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 200);
        $bookmarks_url = \Minz\Url::for('bookmarks');
        $bookmarks_anchor = "<a class=\"anchor--hidden\" href=\"{$bookmarks_url}\">bookmarks</a>";
        $this->assertResponseContains($response, "via your <strong>{$bookmarks_anchor}</strong>");
    }

    public function testIndexRendersIfViaFollowedCollections(): void
    {
        $user = $this->login();
        /** @var string */
        $username = $this->fake('username');
        $other_user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
        $collection_name = $this->fake('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $collection_name,
        ]);
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'via_type' => 'collection',
            'via_resource_id' => $collection->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 200);
        $collection_url = \Minz\Url::for('collection', ['id' => $collection->id]);
        $collection_anchor = "<a class=\"anchor--hidden\" href=\"{$collection_url}\">{$collection_name}</a>";
        $profile_url = \Minz\Url::for('profile', ['id' => $other_user->id]);
        $profile_anchor = "<a class=\"anchor--hidden\" href=\"{$profile_url}\">{$username}</a>";
        $this->assertResponseContains($response, "via <strong>{$collection_anchor}</strong> by {$profile_anchor}");
    }

    public function testIndexRendersTipsIfNoNewsFlash(): void
    {
        $user = $this->login();
        $news = $user->news();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        \Minz\Flash::set('no_news', true);

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'There are no relevant links to suggest at this time.');
    }

    public function testIndexHidesAddToCollectionsIfUserHasNoCollections(): void
    {
        $user = $this->login();
        $news = $user->news();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, 'Add to collections');
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
    }

    public function testCreateSelectsLinksFromBookmarksIfTypeIsShort(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        /** @var int */
        $duration = $this->fake('numberBetween', 0, 9);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $link = $link->reload();
        $this->assertSame('bookmarks', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateSelectsLinksFromBookmarksIfTypeIsLong(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        /** @var int */
        $duration = $this->fake('numberBetween', 10, 9000);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
            'type' => 'long',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $link = $link->reload();
        $this->assertSame('bookmarks', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateSelectsLinksFromFollowedIfTypeIsNewsfeed(): void
    {
        $user = $this->login();
        $news = $user->news();
        $other_user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        /** @var string */
        $link_url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => $created_at,
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
            'type' => 'newsfeed',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $news_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $this->assertNotNull($news_link);
        $this->assertSame($link->url, $news_link->url);
        $this->assertSame($link->title, $news_link->title);
        $this->assertSame('collection', $news_link->via_type);
        $this->assertSame($collection->id, $news_link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $news_link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateMemorizesViaIfAleardySet(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        /** @var int */
        $duration = $this->fake('numberBetween', 0, 9);
        $via_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $via_collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $via_link->id,
            'collection_id' => $via_collection->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => $duration,
            'url' => $url,
            'via_type' => 'collection',
            'via_resource_id' => $via_collection->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $link = $link->reload();
        $this->assertSame('collection', $link->via_type);
        $this->assertSame($via_collection->id, $link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateDoesNotDuplicatesLink(): void
    {
        $user = $this->login();
        $news = $user->news();
        $other_user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        /** @var string */
        $link_url = $this->fake('url');
        $owned_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => $created_at,
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
            'type' => 'newsfeed',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $news_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $this->assertNotNull($news_link);
        $this->assertSame($owned_link->id, $news_link->id);
        $this->assertTrue(models\LinkToCollection::existsBy([
            'link_id' => $owned_link->id,
            'collection_id' => $news->id,
        ]));
    }

    public function testCreateSetsFlashNoNewsIfNoSuggestions(): void
    {
        $user = $this->login();

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertTrue(\Minz\Flash::get('no_news'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        /** @var int */
        $duration = $this->fake('numberBetween', 0, 9);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => $user->csrf,
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $link = $link->reload();
        $this->assertSame('', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNull($link_to_news);
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        /** @var int */
        $duration = $this->fake('numberBetween', 0, 9);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => $duration,
            'via_type' => '',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => 'not the token',
            'type' => 'short',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $link = $link->reload();
        $this->assertSame('', $link->via_type);
        $this->assertNull($link->via_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNull($link_to_news);
    }
}
