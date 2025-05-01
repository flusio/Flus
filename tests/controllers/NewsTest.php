<?php

namespace App\controllers;

use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class NewsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

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
        $this->assertResponseTemplateName($response, 'news/index.phtml');
        $this->assertResponseContains($response, $title);
    }

    public function testIndexRendersIfViaBookmarks(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'bookmarks',
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
            'source_type' => 'collection',
            'source_resource_id' => $collection->id,
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

    public function testCreateSelectsLinksFromFollowed(): void
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
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $news_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $this->assertNotNull($news_link);
        $this->assertSame($link->url, $news_link->url);
        $this->assertSame($link->title, $news_link->title);
        $this->assertSame('collection', $news_link->source_type);
        $this->assertSame($collection->id, $news_link->source_resource_id);
        $link_to_news = models\LinkToCollection::findBy([
            'link_id' => $news_link->id,
            'collection_id' => $news->id,
        ]);
        $this->assertNotNull($link_to_news);
    }

    public function testCreateGroupsLinksBySourcesGroups(): void
    {
        $user = $this->login();
        $news = $user->news();
        $other_user = UserFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link3 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => \Minz\Time::ago(1, 'day'),
            'link_id' => $link1->id,
            'collection_id' => $collection->id,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => \Minz\Time::ago(1, 'day'),
            'link_id' => $link2->id,
            'collection_id' => $collection->id,
        ]);
        LinkToCollectionFactory::create([
            'created_at' => \Minz\Time::ago(2, 'days'),
            'link_id' => $link3->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', '/news', [
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $news_links = $news->links(['published_at']);
        $this->assertSame(3, count($news_links));
        $this->assertTrue($news_links[0]->group_by_source);
        $this->assertTrue($news_links[1]->group_by_source);
        // This one is published a different day, so it's not grouped.
        $this->assertFalse($news_links[2]->group_by_source);
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
            'csrf' => \App\Csrf::generate(),
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
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertTrue(\Minz\Flash::get('no_news'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
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
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]));
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
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
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]));
    }
}
