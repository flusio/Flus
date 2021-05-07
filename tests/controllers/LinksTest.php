<?php

namespace flusio\controllers;

use flusio\models;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
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

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/show.phtml');
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

        $this->assertResponse($response, 200, nl2br($content));
    }

    public function testShowRendersFeedCorrectly()
    {
        $title = $this->fake('words', 3, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $title,
            'is_hidden' => 0,
        ]);
        $content = $this->fake('paragraphs', 3, true);
        $this->create('message', [
            'link_id' => $link_id,
            'content' => $content,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/feed.atom.xml");

        $this->assertResponse($response, 200, nl2br($content));
        $this->assertPointer($response, 'links/feed.atom.xml.phtml');
        $content_type = $response->headers(true)['Content-Type'];
        $this->assertSame('application/atom+xml;charset=UTF-8', $content_type);
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

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/show_public.phtml');
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

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/show_public.phtml');
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

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}");
    }

    public function testShowFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id');

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
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

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
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

        $this->assertResponse($response, 200, 'New link');
        $this->assertPointer($response, 'links/new.phtml');
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

        $this->assertResponse($response, 200, $url);
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
            'collection' => $collection_id,
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
        $this->assertResponse($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testCreateCreatesLinkAndRedirects()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $url = 'https://github.com/flusio/flusio';
        $from = \Minz\Url::for('bookmarks');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = models\Link::take();
        $this->assertResponse($response, 302, $from);
        $this->assertSame($url, $link->url);
        $this->assertSame('flusio/flusio', $link->title);
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
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = models\Link::find($link_id);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateCreatesLinkIfItExistsForAnotherUser()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $this->assertSame(2, models\Link::count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = models\Link::findBy(['user_id' => $user->id]);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateHandlesMultipleCollections()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $this->assertSame(1, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id_1, $collection_id_2],
            'from' => $from,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(2, $links_to_collections_dao->count());

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
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'url' => $url,
            'collection_ids' => [$collection_id],
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponse($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

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

        $this->assertResponse($response, 400, 'A security verification failed');
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

        $this->assertResponse($response, 400, 'Link scheme must be either http or https.');
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

        $this->assertResponse($response, 400, 'The link is required.');
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

        $this->assertResponse($response, 400, 'One of the associated collection doesn’t exist.');
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

        $this->assertResponse($response, 400, 'The link must be associated to a collection.');
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

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'links/edit.phtml');
    }

    public function testEditFailsIfNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/edit");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Fedit");
    }

    public function testEditFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id/edit');

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
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

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
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

        $this->assertResponse($response, 302, "/links/{$link_id}");
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

        $this->assertResponse($response, 302, $from);
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

        $this->assertResponse($response, 302, "/links/{$link_id}");
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

        $this->assertResponse($response, 302, "/links/{$link_id}");
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
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'title' => $new_title,
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Fedit");
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

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
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

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
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

        $this->assertResponse($response, 302, '/');
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

        $this->assertResponse($response, 302, '/bookmarks');
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

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}");
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

        $this->assertResponse($response, 404);
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

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $this->assertTrue(models\Link::exists($link_id));
        $this->assertFlash('error', 'A security verification failed.');
    }

    public function testMarkAsRead()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
        $news_link = models\NewsLink::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($news_link);
        $this->assertSame('bookmarks', $news_link->via_type);
        $this->assertNotNull($news_link->read_at);
    }

    public function testMarkAsReadDoesNotCreateNewsLinkIfExisting()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
            'via_type' => 'followed',
            'read_at' => null,
        ]);

        $this->assertSame(1, models\NewsLink::count());

        $response = $this->appRun('post', "/links/{$link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
        $this->assertSame(1, models\NewsLink::count());
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertSame('bookmarks', $news_link->via_type);
        $this->assertNotNull($news_link->read_at);
    }

    public function testMarkAsReadWorksEvenIfNotInBookmarks()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $news_link = models\NewsLink::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($news_link);
        $this->assertSame('bookmarks', $news_link->via_type);
        $this->assertNotNull($news_link->read_at);
    }

    public function testMarkAsReadRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/mark-as-read", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarks');
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
        $news_link = models\NewsLink::findBy([
            'user_id' => $user_id,
            'url' => $url,
        ]);
        $this->assertNull($news_link);
    }

    public function testMarkAsReadFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/mark-as-read", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
        $news_link = models\NewsLink::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($news_link);
    }

    public function testMarkAsReadFailsIfNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
        $news_link = models\NewsLink::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($news_link);
    }
}
