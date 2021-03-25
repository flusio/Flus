<?php

namespace flusio\controllers;

use flusio\models;

class LinkCollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_id_3 = $this->create('collection', [
            'user_id' => $user->id,
            'name' => $collection_name,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections");

        $this->assertResponse($response, 200, $collection_name);
        $this->assertPointer($response, 'link_collections/index.phtml');
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Fcollections");
    }

    public function testIndexFailsIfLinkIsNotFound()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections");

        $this->assertResponse($response, 404);
    }

    public function testUpdateChangesCollectionsAndRedirects()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_to_collection_1 = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $link_to_collection_2 = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);
        $this->assertNull($link_to_collection_1);
        $this->assertNotNull($link_to_collection_2);
    }

    public function testUpdateRedirectsToFrom()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection_id],
            'from' => \Minz\Url::for('collection', ['id' => $collection_id]),
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}");
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Fcollections");
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_to_collection_1 = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $link_to_collection_2 = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfLinkIsNotFound()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponse($response, 404);
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_to_collection_1 = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $link_to_collection_2 = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfCollectionIdsContainsNotOwnedId()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $this->assertFlash('error', 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, $links_to_collections_dao->count());
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => 'not the token',
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertSame(0, $links_to_collections_dao->count());
    }
}
