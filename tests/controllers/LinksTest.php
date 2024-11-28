<?php

namespace App\controllers;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\GroupFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\MessageFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;

    #[\PHPUnit\Framework\Attributes\Before]
    public function emptyCachePath(): void
    {
        $files = glob(\App\Configuration::$application['cache_path'] . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('words', 3, true);
        $group = GroupFactory::create([
            'name' => $group_name,
            'user_id' => $user->id,
        ]);
        /** @var string */
        $collection_name_1 = $this->fake('words', 3, true);
        CollectionFactory::create([
            'name' => $collection_name_1,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        /** @var string */
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
    }

    public function testIndexRendersResultsWhenQuery(): void
    {
        $user = $this->login();
        /** @var string */
        $title_1 = $this->fakeUnique('words', 3, true);
        /** @var string */
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

    public function testIndexRendersCollectionsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
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

    public function testIndexDoesNotRenderCollectionsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
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

    public function testIndexRedirectsToLoginIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/links');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Flinks');
    }

    public function testIndexRedirectsIfPageIsOutOfBound(): void
    {
        $user = $this->login();
        /** @var string */
        $title_1 = $this->fakeUnique('words', 3, true);
        /** @var string */
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

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $title = $this->fake('words', 3, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
            'title' => $title,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowDisplaysMessages(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
        ]);
        MessageFactory::create([
            'link_id' => $link->id,
            'content' => '**foo bar**',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '<strong>foo bar</strong>');
    }

    public function testShowRendersCorrectlyIfNotHiddenAndNotConnected(): void
    {
        /** @var string */
        $title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'fetched_at' => \Minz\Time::now(),
            'title' => $title,
            'is_hidden' => false,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowRendersCorrectlyIfNotHiddenAndDoesNotOwnTheLink(): void
    {
        $this->login();
        /** @var string */
        $title = $this->fake('words', 3, true);
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => \Minz\Time::now(),
            'title' => $title,
            'is_hidden' => false,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowRendersCorrectlyIfHiddenAndNotOwnedButInOwnedCollection(): void
    {
        $current_user = $this->login();
        /** @var string */
        $title = $this->fake('words', 3, true);
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testShowRendersCorrectlyIfHiddenButInSharedCollection(): void
    {
        $current_user = $this->login();
        /** @var string */
        $title = $this->fake('words', 3, true);
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testShowRedirectsIfHiddenAndNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
            'title' => $title,
            'is_hidden' => true,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}");
    }

    public function testShowFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/links/not-a-valid-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserDoesNotOwnThePrivateLink(): void
    {
        $current_user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => \Minz\Time::now(),
            'title' => $title,
            'is_hidden' => true,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}");

        $this->assertResponseCode($response, 404);
    }

    public function testNewRendersCorrectly(): void
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
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $output = $response->output();
        $this->assertInstanceOf(\Minz\Output\View::class, $output);
        /** @var array<string, mixed> */
        $variables = $output->variables();
        $this->assertIsArray($variables['collection_ids']);
        $this->assertContains($bookmarks->id, $variables['collection_ids']);
    }

    public function testNewPrefillsUrl(): void
    {
        $user = $this->login();
        CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        /** @var string */
        $url = $this->fake('url');
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'url' => $url,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $url);
    }

    public function testNewPrefillsCollection(): void
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

        $this->assertInstanceOf(\Minz\Response::class, $response);
        $output = $response->output();
        $this->assertInstanceOf(\Minz\Output\View::class, $output);
        /** @var array<string, mixed> */
        $variables = $output->variables();
        $this->assertIsArray($variables['collection_ids']);
        $this->assertContains($collection->id, $variables['collection_ids']);
    }

    public function testNewRendersCollectionSharedWithWriteAccess(): void
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

    public function testNewDoesNotRenderCollectionSharedWithReadAccess(): void
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

    public function testNewRedirectsIfNotConnected(): void
    {
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/links/new', [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testCreateCreatesLinkAndRedirects(): void
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

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertSame($url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
        $this->assertFalse($link->is_hidden);
    }

    public function testCreateAllowsToCreateHiddenLinks(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/Flus',
            'collection_ids' => [$collection->id],
            'is_hidden' => true,
            'from' => $from,
        ]);

        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertTrue($link->is_hidden);
    }

    public function testCreateDoesNotCreateLinkIfItExists(): void
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/Flus';
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

    public function testCreateCreatesLinkIfItExistsForAnotherUser(): void
    {
        $user = $this->login();
        $another_user = UserFactory::create();
        $url = 'https://github.com/flusio/Flus';
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
        $this->assertNotNull($link);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateHandlesMultipleCollections(): void
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/Flus';
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

    public function testCreateWorksIfCollectionIsSharedWithWriteAccess(): void
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
        $this->assertNotNull($link);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateCanCreateCollections(): void
    {
        $user = $this->login();
        /** @var string */
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
        $this->assertNotNull($link);
        $collection = models\Collection::findBy([
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $this->assertResponseCode($response, 302, $from);
        $this->assertNotNull($collection);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $url = 'https://github.com/flusio/Flus';
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

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => 'not the token',
            'url' => 'https://github.com/flusio/Flus',
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid(): void
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

    public function testCreateFailsIfUrlIsMissing(): void
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

    public function testCreateFailsIfCollectionIdsContainsNotOwnedId(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/Flus',
            'collection_ids' => [$collection->id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsMissing(): void
    {
        $user = $this->login();
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/Flus',
            'collection_ids' => [],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link must be associated to a collection.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsSharedWithReadAccess(): void
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

    public function testCreateFailsIfCollectionIsNotShared(): void
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

    public function testCreateFailsIfNewCollectionNameIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
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

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/edit.phtml');
    }

    public function testEditFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fedit");
    }

    public function testEditFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/links/not-a-valid-id/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserDoesNotOwnTheLink(): void
    {
        $current_user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/edit");

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesTheLinkAndRedirects(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        /** @var string */
        $new_title = $this->fake('words', 5, true);
        /** @var int */
        $old_reading_time = $this->fakeUnique('numberBetween', 0, 9000);
        /** @var int */
        $new_reading_time = $this->fakeUnique('numberBetween', 0, 9000);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testUpdateRedirectsToFrom(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        /** @var string */
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        /** @var string */
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testUpdateFailsIfTitleIsInvalid(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        $new_title = '';
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testUpdateFailsIfNotConnected(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        /** @var string */
        $new_title = $this->fake('words', 5, true);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testUpdateFailsIfLinkDoesNotExist(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        /** @var string */
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testUpdateFailsIfUserDoesNotOwnTheLink(): void
    {
        /** @var string */
        $old_title = $this->fake('words', 3, true);
        /** @var string */
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'fetched_at' => \Minz\Time::now(),
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

    public function testDeleteDeletesLinkAndRedirects(): void
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

    public function testDeleteRedirectsToRedirectToIfGiven(): void
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

    public function testDeleteRedirectsIfNotConnected(): void
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

    public function testDeleteFailsIfLinkIsNotOwned(): void
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

    public function testDeleteFailsIfCsrfIsInvalid(): void
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
