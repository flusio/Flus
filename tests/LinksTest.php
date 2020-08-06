<?php

namespace flusio;

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

    public function testShowRendersCorrectlyIfPublicAndNotConnected()
    {
        $title = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'fetched_at' => $this->fake('iso8601'),
            'title' => $title,
            'is_public' => true,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/show_public.phtml');
    }

    public function testShowRendersCorrectlyIfPublicAndDoesNotOwnTheLink()
    {
        $this->login();
        $title = $this->fake('words', 3, true);
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $title,
            'is_public' => 1,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/show_public.phtml');
    }

    public function testShowRedirectsIfNotFetched()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => null,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponse($response, 302, "/links/{$link_id}/fetch");
    }

    public function testShowRedirectsIfPrivateAndNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => $this->fake('iso8601'),
            'title' => $this->fake('words', 3, true),
            'is_public' => 0,
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
            'is_public' => 0,
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

        $response = $this->appRun('get', '/links/new');

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

        $response = $this->appRun('get', '/links/new', [
            'url' => $url,
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

        $response = $this->appRun('get', '/links/new', [
            'collection' => $collection_id,
        ]);

        $variables = $response->output()->variables();
        $this->assertContains($collection_id, $variables['collection_ids']);
    }

    public function testNewRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/links/new');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Flinks%2Fnew');
    }

    public function testNewRedirectsIfNotConnectedAndKeepsUrl()
    {
        $url = $this->fake('url');

        $response = $this->appRun('get', '/links/new', [
            'url' => $url
        ]);

        $this->assertResponse(
            $response,
            302,
            '/login?redirect_to=%2Flinks%2Fnew%3Furl%3D' . urlencode(urlencode($url))
        );
    }

    public function testCreateCreatesLinkAndRedirects()
    {
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $url = $this->fake('url');

        $this->assertSame(0, $link_dao->count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->listAll()[0]);

        $this->assertResponse($response, 302, "/links/{$link->id}");
        $this->assertSame($url, $link->url);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateDoesNotCreateLinkIfItExists()
    {
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->find($link_id));
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateCreatesLinkIfItExistsForAnotherUser()
    {
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $another_user_id = $this->create('user');
        $url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $this->create('link', [
            'user_id' => $another_user_id,
            'url' => $url,
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertSame(2, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->findBy(['user_id' => $user->id]));
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateHandlesMultipleCollections()
    {
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $url = $this->fake('url');
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

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'collection_ids' => [$collection_id_1, $collection_id_2],
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(2, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->find($link_id));
        $this->assertContains($collection_id_1, array_column($link->collections(), 'id'));
        $this->assertContains($collection_id_2, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $link_dao = new models\dao\Link();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $url = $this->fake('url');

        $response = $this->appRun('post', '/links/new', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse(
            $response,
            302,
            '/login?redirect_to=%2Flinks%2Fnew%3Furl%3D' . urlencode(urlencode($url))
        );
        $this->assertSame(0, $link_dao->count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/links/new', [
            'csrf' => 'not the token',
            'url' => $this->fake('url'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, $link_dao->count());
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => 'ftp://' . $this->fake('domainName'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 400, 'Link scheme must be either http or https.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testCreateFailsIfUrlIsMissing()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 400, 'The link is required.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testCreateFailsIfCollectionDoesNotExist()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $this->fake('url'),
            'collection_ids' => ['does not exist'],
        ]);

        $this->assertResponse($response, 400, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testCreateFailsIfCollectionIsMissing()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();

        $response = $this->appRun('post', '/links/new', [
            'csrf' => $user->csrf,
            'url' => $this->fake('url'),
            'collection_ids' => [],
        ]);

        $this->assertResponse($response, 400, 'The link must be associated to a collection.');
        $this->assertSame(0, $link_dao->count());
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
        $link_dao = new models\dao\Link();
        $old_title = $this->fake('words', 3, true);
        $new_title = $this->fake('words', 5, true);
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
        $db_link = $link_dao->find($link_id);
        $this->assertSame($new_title, $db_link['title']);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $link_dao = new models\dao\Link();
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

        $this->assertResponse($response, 400, 'A security verification failed');
        $db_link = $link_dao->find($link_id);
        $this->assertSame($old_title, $db_link['title']);
    }

    public function testUpdateFailsIfTitleIsInvalid()
    {
        $link_dao = new models\dao\Link();
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

        $this->assertResponse($response, 400, 'The title is required');
        $db_link = $link_dao->find($link_id);
        $this->assertSame($old_title, $db_link['title']);
    }

    public function testUpdateFailsIfNotConnected()
    {
        $link_dao = new models\dao\Link();
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
        $db_link = $link_dao->find($link_id);
        $this->assertSame($old_title, $db_link['title']);
    }

    public function testUpdateFailsIfLinkDoesNotExist()
    {
        $link_dao = new models\dao\Link();
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
        $db_link = $link_dao->find($link_id);
        $this->assertSame($old_title, $db_link['title']);
    }

    public function testUpdateFailsIfUserDoesNotOwnTheLink()
    {
        $link_dao = new models\dao\Link();
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
        $db_link = $link_dao->find($link_id);
        $this->assertSame($old_title, $db_link['title']);
    }

    public function testShowFetchRendersCorrectly()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => null,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 200, 'Please wait');
        $this->assertPointer($response, 'links/show_fetch.phtml');
    }

    public function testShowFetchWithFetchedLinkRendersCorrectly()
    {
        $title = $this->fake('words', 3, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $title,
            'fetched_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 200, $title);
    }

    public function testShowFetchFailsIfNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => null,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Ffetch");
    }

    public function testShowFetchFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id/fetch');

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
    }

    public function testShowFetchFailsIfUserDoesNotOwnTheLink()
    {
        $current_user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
    }

    public function testFetchUpdatesLinkWithTheTitleAndRedirects()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $expected_title = 'GitHub - flusio/flusio: The citizen social media';
        $this->assertResponse($response, 200, $expected_title);
        $this->assertPointer($response, 'links/show_fetch.phtml');
        $db_link = $link_dao->find($link_id);
        $this->assertSame($expected_title, $db_link['title']);
        $this->assertSame(200, $db_link['fetched_code']);
    }

    public function testFetchSavesResponseInCache()
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $hash = \SpiderBits\Cache::hash($url);
        $cache_filepath = \Minz\Configuration::$application['cache_path'] . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testFetchUsesCache()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
        ]);
        $expected_title = 'The foo bar baz';
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = <<<TEXT
        HTTP/2 200 OK
        Content-Type: text/html

        <html>
            <head>
                <title>{$expected_title}</title>
            </head>
        </html>
        TEXT;
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $db_link = $link_dao->find($link_id);
        $this->assertSame($expected_title, $db_link['title']);
    }

    public function testFetchDoesNotChangeTitleIfUnreachable()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://flus.fr/does_not_exist.html',
            'title' => 'https://flus.fr/does_not_exist.html',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $expected_title = 'https://flus.fr/does_not_exist.html';
        $this->assertResponse($response, 200, $expected_title);
        $this->assertPointer($response, 'links/show_fetch.phtml');
        $db_link = $link_dao->find($link_id);
        $this->assertSame($expected_title, $db_link['title']);
        $this->assertSame(404, $db_link['fetched_code']);
    }

    public function testFetchFailsIfCsrfIsInvalid()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $db_link['title']);
    }

    public function testFetchFailsIfNotConnected()
    {
        $link_dao = new models\dao\Link();

        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Ffetch");
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $db_link['title']);
    }

    public function testFetchFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('post', "/links/do-not-exist/fetch", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404, 'This page doesn’t exist');
    }

    public function testFetchFailsIfUserDoesNotOwnTheLink()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404, 'This page doesn’t exist');
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $db_link['title']);
    }
}
