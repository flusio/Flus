<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\GroupFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\MessageFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @before
     */
    public function emptyCachePath()
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $group_name = $this->fake('words', 3, true);
        $group = GroupFactory::create([
            'name' => $group_name,
            'user_id' => $user->id,
        ]);
        $collection_name_1 = $this->fake('words', 3, true);
        CollectionFactory::create([
            'name' => $collection_name_1,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_name_2 = $this->fake('words', 3, true);
        CollectionFactory::create([
            'name' => $collection_name_2,
            'group_id' => $group->id,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', '/links');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/index.phtml');
        $this->assertResponseContains($response, $group_name);
        $this->assertResponseContains($response, $collection_name_1);
        $this->assertResponseContains($response, $collection_name_2);
        $this->assertResponseContains($response, 'Your links to read later');
        $this->assertResponseContains($response, 'Your links marked as read');
    }

    public function testIndexRendersResultsWhenQuery()
    {
        $user = $this->login();
        $title_1 = $this->fakeUnique('words', 3, true);
        $title_2 = $this->fakeUnique('words', 3, true);
        $query = $title_1;
        LinkFactory::create([
            'title' => $title_1,
        ]);
        LinkFactory::create([
            'title' => $title_2,
        ]);

        $response = $this->appRun('GET', '/links', [
            'q' => $query,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/search.phtml');
        $this->assertResponseContains($response, $title_1);
        $this->assertResponseNotContains($response, $title_2);
    }

    public function testIndexRendersCollectionsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', '/links');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_name);
    }

    public function testIndexDoesNotRenderCollectionsSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', '/links');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testIndexRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('GET', '/links');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Flinks');
    }

    public function testIndexRedirectsIfPageIsOutOfBound()
    {
        $user = $this->login();
        $title_1 = $this->fakeUnique('words', 3, true);
        $title_2 = $this->fakeUnique('words', 3, true);
        $query = $title_1;
        LinkFactory::create([
            'title' => $title_1,
        ]);

        $response = $this->appRun('GET', '/links', [
            'q' => $query,
            'page' => 2,
        ]);

        $query_encoded = urlencode($query);
        $this->assertResponseCode($response, 302, "/links?q={$query_encoded}&page=1");
    }

    public function testShowRendersCorrectly()
    {
        $title = $this->fake('words', 3, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $title,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowDisplaysMessages()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
        ]);
        MessageFactory::create([
            'link_id' => $link->id,
            'content' => '**foo bar**',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '<strong>foo bar</strong>');
    }

    public function testShowRendersCorrectlyIfNotHiddenAndNotConnected()
    {
        $title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'fetched_at' => $this->fake('dateTime'),
            'title' => $title,
            'is_hidden' => false,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowRendersCorrectlyIfNotHiddenAndDoesNotOwnTheLink()
    {
        $this->login();
        $title = $this->fake('words', 3, true);
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $title,
            'is_hidden' => false,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowRendersCorrectlyIfHiddenAndNotOwnedButInOwnedCollection()
    {
        $current_user = $this->login();
        $title = $this->fake('words', 3, true);
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $title,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $current_user->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowRendersCorrectlyIfHiddenButInSharedCollection()
    {
        $current_user = $this->login();
        $title = $this->fake('words', 3, true);
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $title,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $current_user->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowRedirectsIfHiddenAndNotConnected()
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $this->fake('words', 3, true),
            'is_hidden' => true,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}");
    }

    public function testShowFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/links/not-a-valid-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserDoesNotOwnThePrivateLink()
    {
        $current_user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $this->fake('words', 3, true),
            'is_hidden' => true,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 404);
    }

    public function testNewRendersCorrectly()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'New link');
        $this->assertResponsePointer($response, 'links/new.phtml');
        $variables = $response->output()->variables();
        $this->assertContains($bookmarks->id, $variables['collection_ids']);
    }

    public function testNewPrefillsUrl()
    {
        $user = $this->login();
        CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $url = $this->fake('url');
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'url' => $url,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $url);
    }

    public function testNewPrefillsCollection()
    {
        $user = $this->login();
        CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'collection_id' => $collection->id,
            'from' => $from,
        ]);

        $variables = $response->output()->variables();
        $this->assertContains($collection->id, $variables['collection_ids']);
    }

    public function testNewRendersCollectionSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection->id);
    }

    public function testNewDoesNotRenderCollectionSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection->id);
    }

    public function testNewRedirectsIfNotConnected()
    {
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testCreateCreatesLinkAndRedirects()
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $link = models\Link::take();
        $this->assertResponseCode($response, 302, $from);
        $this->assertSame($url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
        $this->assertFalse($link->is_hidden);
    }

    public function testCreateAllowsToCreateHiddenLinks()
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [$collection->id],
            'is_hidden' => true,
            'from' => $from,
        ]);

        $link = models\Link::take();
        $this->assertTrue($link->is_hidden);
    }

    public function testCreateDoesNotCreateLinkIfItExists()
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $link = $link->reload();
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateCreatesLinkIfItExistsForAnotherUser()
    {
        $user = $this->login();
        $another_user = UserFactory::create();
        $url = 'https://github.com/flusio/flusio';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        LinkFactory::create([
            'user_id' => $another_user->id,
            'url' => $url,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertSame(2, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $link = models\Link::findBy(['user_id' => $user->id]);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateHandlesMultipleCollections()
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_1->id, $collection_2->id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(2, models\LinkToCollection::count());

        $link = $link->reload();
        $collection_ids = array_column($link->collections(), 'id');
        $this->assertContains($collection_1->id, $collection_ids);
        $this->assertContains($collection_2->id, $collection_ids);
    }

    public function testCreateWorksIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::take();
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateCanCreateCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 3, true);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'new_collection_names' => [$collection_name],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(2, models\Collection::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $link = models\Link::take();
        $collection = models\Collection::findBy([
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $this->assertResponseCode($response, 302, $from);
        $this->assertNotNull($collection);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $url = 'https://github.com/flusio/flusio';
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => \Minz\Csrf::generate(),
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => 'not the token',
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'ftp://' . $this->fake('domainName'),
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsMissing()
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIdsContainsNotOwnedId()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsMissing()
    {
        $user = $this->login();
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link must be associated to a collection.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testCreateFailsIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testCreateFailsIfNewCollectionNameIsInvalid()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 100, true);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'new_collection_names' => [$collection_name],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters');
        $this->assertSame(0, models\Link::count());
        $this->assertSame(1, models\Collection::count()); // this counts the bookmarks collection
    }

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/edit.phtml');
    }

    public function testEditFailsIfNotConnected()
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fedit");
    }

    public function testEditFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/links/not-a-valid-id/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserDoesNotOwnTheLink()
    {
        $current_user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => $this->fake('dateTime'),
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/edit");

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesTheLinkAndRedirects()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $old_reading_time = $this->fakeUnique('numberBetween', 0, 9000);
        $new_reading_time = $this->fakeUnique('numberBetween', 0, 9000);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
            'reading_time' => $old_reading_time,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
            'reading_time' => $new_reading_time,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}");
        $link = $link->reload();
        $this->assertSame($new_title, $link->title);
        $this->assertSame($new_reading_time, $link->reading_time);
    }

    public function testUpdateRedirectsToFrom()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', "/links/{$link->id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $link = $link->reload();
        $this->assertSame($new_title, $link->title);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/edit", [
            'csrf' => 'not the token',
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'links/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfTitleIsInvalid()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = '';
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'links/edit.phtml');
        $this->assertResponseContains($response, 'The title is required.');
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfNotConnected()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/edit", [
            'csrf' => \Minz\Csrf::generate(),
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fedit");
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfLinkDoesNotExist()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('POST', '/links/not-the-id/edit', [
            'csrf' => $user->csrf,
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfUserDoesNotOwnTheLink()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => $this->fake('dateTime'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
    }

    public function testDeleteDeletesLinkAndRedirects()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/links/{$link->id}",
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Link::exists($link->id));
    }

    public function testDeleteRedirectsToRedirectToIfGiven()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/links/{$link->id}",
            'redirect_to' => '/bookmarks',
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/delete", [
            'csrf' => 'a token',
            'from' => "/links/{$link->id}",
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}");
        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testDeleteFailsIfLinkIsNotOwned()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/links/{$link->id}",
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/delete", [
            'csrf' => 'not the token',
            'from' => "/links/{$link->id}",
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}");
        $this->assertTrue(models\Link::exists($link->id));
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
    }
}
