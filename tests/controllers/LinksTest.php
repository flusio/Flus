<?php

namespace flusio\controllers;

use flusio\models;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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
        $group_id = $this->create('group', [
            'name' => $group_name,
            'user_id' => $user->id,
        ]);
        $collection_name_1 = $this->fake('words', 3, true);
        $this->create('collection', [
            'name' => $collection_name_1,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_name_2 = $this->fake('words', 3, true);
        $this->create('collection', [
            'name' => $collection_name_2,
            'group_id' => $group_id,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', '/links');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/index.phtml');
        $this->assertResponseContains($response, $group_name);
        $this->assertResponseContains($response, $collection_name_1);
        $this->assertResponseContains($response, $collection_name_2);
        $this->assertResponseContains($response, 'Your links to read later');
        $this->assertResponseContains($response, 'All your links marked as read');
    }

    public function testIndexRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('get', '/links');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Flinks');
    }

    public function testShowRendersCorrectly()
    {
        $title = $this->fake('words', 3, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show.phtml');
    }

    public function testShowDisplaysMessages()
    {
        $content = $this->fake('paragraphs', 3, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
        ]);
        $this->create('message', [
            'link_id' => $link_id,
            'content' => $content,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, nl2br($content));
    }

    public function testShowRendersCorrectlyIfNotHiddenAndNotConnected()
    {
        $title = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'fetched_at' => $this->fake('iso8601'),
            'title' => $title,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show_public.phtml');
    }

    public function testShowRendersCorrectlyIfNotHiddenAndDoesNotOwnTheLink()
    {
        $this->login();
        $title = $this->fake('words', 3, true);
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $title,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
        $this->assertResponsePointer($response, 'links/show_public.phtml');
    }

    public function testShowRedirectsIfHiddenAndNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $this->fake('words', 3, true),
            'is_hidden' => 1,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}");
    }

    public function testShowFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserDoesNotOwnThePrivateLink()
    {
        $current_user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $this->fake('words', 3, true),
            'is_hidden' => 1,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponseCode($response, 404);
    }

    public function testNewRendersCorrectly()
    {
        $user = $this->login();
        $bookmarks_collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', '/links/new', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'New link');
        $this->assertResponsePointer($response, 'links/new.phtml');
        $variables = $response->output()->variables();
        $this->assertContains($bookmarks_collection_id, $variables['collection_ids']);
    }

    public function testNewPrefillsUrl()
    {
        $user = $this->login();
        $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $url = $this->fake('url');
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', '/links/new', [
            'url' => $url,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $url);
    }

    public function testNewPrefillsCollection()
    {
        $user = $this->login();
        $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', '/links/new', [
            'collection_id' => $collection_id,
            'from' => $from,
        ]);

        $variables = $response->output()->variables();
        $this->assertContains($collection_id, $variables['collection_ids']);
    }

    public function testNewRedirectsIfNotConnected()
    {
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', '/links/new', [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testCreateCreatesLinkAndRedirects()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
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
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
        $this->assertFalse($link->is_hidden);
    }

    public function testCreateAllowsToCreateHiddenLinks()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [$collection_id],
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
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $link = models\Link::find($link_id);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateCreatesLinkIfItExistsForAnotherUser()
    {
        $user = $this->login();
        $another_user_id = $this->create('user');
        $url = 'https://github.com/flusio/flusio';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $this->create('link', [
            'user_id' => $another_user_id,
            'url' => $url,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertSame(2, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $link = models\Link::findBy(['user_id' => $user->id]);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateHandlesMultipleCollections()
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id_1, $collection_id_2],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(2, models\LinkToCollection::count());

        $link = models\Link::find($link_id);
        $this->assertContains($collection_id_1, array_column($link->collections(), 'id'));
        $this->assertContains($collection_id_2, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $url = 'https://github.com/flusio/flusio';
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => \Minz\CSRF::generate(),
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => 'not the token',
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'ftp://' . $this->fake('domainName'),
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'Link scheme must be either http or https.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsMissing()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIdsContainsNotOwnedId()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [$collection_id],
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

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'https://github.com/flusio/flusio',
            'collection_ids' => [],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link must be associated to a collection.');
        $this->assertSame(0, models\Link::count());
    }

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/edit.phtml');
    }

    public function testEditFailsIfNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Fedit");
    }

    public function testEditFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserDoesNotOwnTheLink()
    {
        $current_user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'fetched_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/edit");

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesTheTitleAndRedirects()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $old_hidden = 0;
        $new_hidden = 1;
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
            'is_hidden' => $old_hidden,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
            'is_hidden' => $new_hidden,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link_id}");
        $link = models\Link::find($link_id);
        $this->assertSame($new_title, $link->title);
        $this->assertTrue($link->is_hidden);
    }

    public function testUpdateRedirectsToFrom()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $old_hidden = 0;
        $new_hidden = 1;
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
            'is_hidden' => $old_hidden,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', "/links/{$link_id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
            'is_hidden' => $new_hidden,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::find($link_id);
        $this->assertSame($new_title, $link->title);
        $this->assertTrue($link->is_hidden);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/edit", [
            'csrf' => 'not the token',
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link_id}");
        $this->assertFlash('error', 'A security verification failed.');
        $link = models\Link::find($link_id);
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfTitleIsInvalid()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = '';
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link_id}");
        $this->assertFlash('errors', [
            'title' => 'The title is required.',
        ]);
        $link = models\Link::find($link_id);
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfNotConnected()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/edit", [
            'csrf' => \Minz\CSRF::generate(),
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Fedit");
        $link = models\Link::find($link_id);
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfLinkDoesNotExist()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('post', '/links/not-the-id/edit', [
            'csrf' => $user->csrf,
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 404);
        $link = models\Link::find($link_id);
        $this->assertSame($old_title, $link->title);
    }

    public function testUpdateFailsIfUserDoesNotOwnTheLink()
    {
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $old_title,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/edit", [
            'csrf' => $user->csrf,
            'title' => $new_title,
        ]);

        $this->assertResponseCode($response, 404);
        $link = models\Link::find($link_id);
        $this->assertSame($old_title, $link->title);
    }

    public function testDeleteDeletesLinkAndRedirects()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/links/{$link_id}",
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Link::exists($link_id));
    }

    public function testDeleteRedirectsToRedirectToIfGiven()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/links/{$link_id}",
            'redirect_to' => '/bookmarks',
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/delete", [
            'csrf' => 'a token',
            'from' => "/links/{$link_id}",
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}");
        $this->assertTrue(models\Link::exists($link_id));
    }

    public function testDeleteFailsIfLinkIsNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/links/{$link_id}",
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Link::exists($link_id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/delete", [
            'csrf' => 'not the token',
            'from' => "/links/{$link_id}",
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link_id}");
        $this->assertTrue(models\Link::exists($link_id));
        $this->assertFlash('error', 'A security verification failed.');
    }
}
