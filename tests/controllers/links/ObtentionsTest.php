<?php

namespace flusio\controllers\links;

use flusio\models;

class ObtentionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testNewRendersCorrectly()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/obtain");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/obtentions/new.phtml');
    }

    public function testNewRendersCorrectlyIfExistingLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $title = $this->fake('sentence');
        $url = $this->fake('url');
        $initial_link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
            'title' => $title,
            'url' => $url,
        ]);
        $existing_link_id = $this->create('link', [
            'user_id' => $user->id,
            'is_hidden' => 0,
            'title' => $title,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/links/{$initial_link_id}/obtain");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/obtentions/new.phtml');
    }

    public function testNewRedirectsIfNotConnected()
    {
        $other_user_id = $this->create('user');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
            'title' => $title,
        ]);
        $from = \Minz\Url::for('news');

        $response = $this->appRun('get', "/links/{$link_id}/obtain", [
            'from' => $from,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponse($response, 302, "/login?redirect_to={$encoded_from}");
    }

    public function testNewFailsIfLinkIsHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 1,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/obtain");

        $this->assertResponse($response, 404);
    }

    public function testCreateCreatesALinkAndRedirects()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('news');
        $is_hidden = true;
        $collection_ids = [$collection_id];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/links/{$link_id}/obtain", [
            'csrf' => $user->csrf,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $this->assertSame(2, models\Link::count());

        $this->assertResponse($response, 302, $from);
        $initial_link = models\Link::find($link_id);
        $new_link = models\Link::findBy(['user_id' => $user->id]);
        $db_link_to_collection = $links_to_collections_dao->listAll()[0];
        $this->assertSame($initial_link->title, $new_link->title);
        $this->assertSame($initial_link->url, $new_link->url);
        $this->assertTrue($new_link->is_hidden);
        $this->assertSame($new_link->id, $db_link_to_collection['link_id']);
        $this->assertSame($collection_id, $db_link_to_collection['collection_id']);
    }

    public function testCreateUpdatesExistingLinks()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $url = $this->fake('url');
        $initial_link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $existing_link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $old_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $new_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $existing_link_id,
            'collection_id' => $old_collection_id,
        ]);
        $from = \Minz\Url::for('news');
        $is_hidden = true;
        $collection_ids = [$new_collection_id];

        $this->assertSame(2, models\Link::count());

        $response = $this->appRun('post', "/links/{$initial_link_id}/obtain", [
            'csrf' => $user->csrf,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $this->assertSame(2, models\Link::count());

        $this->assertResponse($response, 302, $from);
        $existing_link = models\Link::find($existing_link_id);
        $this->assertTrue($existing_link->is_hidden);
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
        $new_db_link_to_collection = $links_to_collections_dao->listAll()[0];
        $this->assertSame($existing_link_id, $new_db_link_to_collection['link_id']);
        $this->assertSame($new_collection_id, $new_db_link_to_collection['collection_id']);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $from = urlencode(\Minz\Url::for('news'));
        $is_hidden = true;
        $collection_ids = [$collection_id];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/links/{$link_id}/obtain", [
            'csrf' => 'a token',
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponse($response, 302, "/login?redirect_to={$encoded_from}");
        $this->assertSame(1, models\Link::count());
    }

    public function testCreateFailsIfLinkIsHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('news');
        $is_hidden = true;
        $collection_ids = [$collection_id];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/links/{$link_id}/obtain", [
            'csrf' => $user->csrf,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(1, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('news');
        $is_hidden = true;
        $collection_ids = [$collection_id];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/links/{$link_id}/obtain", [
            'csrf' => 'not the token',
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(1, models\Link::count());
    }

    public function testCreateFailsIfCollectionIdsIsEmpty()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('news');
        $is_hidden = true;
        $collection_ids = [];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/links/{$link_id}/obtain", [
            'csrf' => $user->csrf,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $this->assertResponse($response, 400, 'The link must be associated to a collection.');
        $this->assertSame(1, models\Link::count());
    }

    public function testCreateFailsIfCollectionIdsContainsNotOwnedId()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('news');
        $is_hidden = true;
        $collection_ids = [$collection_id];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/links/{$link_id}/obtain", [
            'csrf' => $user->csrf,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'from' => $from,
        ]);

        $this->assertResponse($response, 400, 'One of the associated collection doesn’t exist.');
        $this->assertSame(1, models\Link::count());
    }
}
