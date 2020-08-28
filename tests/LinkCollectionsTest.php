<?php

namespace flusio;

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

        $this->assertResponse($response, 400, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, $links_to_collections_dao->count());
    }

    public function testBookmarkAddsLinkToBookmarks()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/bookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNotNull($db_link_to_collection);
    }

    public function testBookmarkRedirectsToLoginIfNotConnected()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/bookmark", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarks');
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNull($db_link_to_collection);
    }

    public function testBookmarkFailsIfCsrfIsInvalid()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/bookmark", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNull($db_link_to_collection);
    }

    public function testBookmarkFailsIfLinkDoesNotExist()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('post', "/links/not-an-id/bookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'The link doesn’t exist.');
    }

    public function testBookmarkFailsIfUserDoesNotOwnTheLink()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/bookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNull($db_link_to_collection);
    }

    public function testBookmarkFailsIfThereIsRelationBetweenLinkAndCollection()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/bookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'This link is already bookmarked.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testUnbookmarkRemovesLinkFromBookmarks()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/unbookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testUnbookmarkRedirectsToLoginIfNotConnected()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/unbookmark", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testUnbookmarkFailsIfCsrfIsInvalid()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/unbookmark", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testUnbookmarkFailsIfLinkDoesNotExist()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/not-an-id/unbookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testUnbookmarkFailsIfUserDoesNotOwnTheLink()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/unbookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testUnbookmarkFailsIfThereIsNoRelationBetweenLinkAndCollection()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/unbookmark", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'This link is not bookmarked.');
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNull($db_link_to_collection);
    }
}
