<?php

namespace flusio\controllers\links;

use flusio\models;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponsePointer($response, 'links/collections/index.phtml');
    }

    public function testIndexRendersCorrectlyWhenMarkAsReadIsSet()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Store the link and mark as read');
    }

    public function testIndexRendersExistingLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id_not_owned = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $link_id_owned = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_id_not_owned = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);
        $collection_id_owned = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_not_owned,
            'collection_id' => $collection_id_not_owned,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_owned,
            'collection_id' => $collection_id_owned,
        ]);

        $response = $this->appRun('get', "/links/{$link_id_not_owned}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/collections/index.phtml');
        $this->assertResponseContains($response, $link_id_owned);
        $this->assertResponseNotContains($response, $link_id_not_owned);
    }

    public function testIndexRendersCorrectLinkIfDuplicated()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/links/{$link_id_2}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/collections/index.phtml');
        $this->assertResponseContains($response, $link_id_2);
        $this->assertResponseNotContains($response, $link_id_1);
    }

    public function testIndexRendersIfUrlAddedByAnotherUserInCollectionOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $other_link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $other_link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'user_id' => $other_user_id,
            'collection_id' => $collection_id,
            'type' => 'write',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $other_link_id);
    }

    public function testIndexRendersIfUrlAddedByOwnerInCollectionWithWriteAccess()
    {
        $user = $this->login();
        $owner_user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $owner_user_id,
            'type' => 'collection',
        ]);
        $owner_link_id = $this->create('link', [
            'user_id' => $owner_user_id,
            'url' => $url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $owner_link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'type' => 'write',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $owner_link_id);
    }

    public function testIndexRendersCollectionSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_id);
    }

    public function testIndexDoesNotCopyNotOwnedAndAccessibleLinks()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
            'url' => $url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'is_public' => 1,
            'name' => $collection_name,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($new_link);
    }

    public function testIndexDoesNotRenderCollectionSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_id);
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

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
    }

    public function testIndexFailsIfLinkIsNotAccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 1,
        ]);
        $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesCollectionsAndRedirects()
    {
        $user = $this->login();
        $is_hidden = $this->fake('boolean');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
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
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id_2],
            'is_hidden' => $is_hidden,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);
        $this->assertNull($link_to_collection_1);
        $this->assertNotNull($link_to_collection_2);
        $link = models\Link::find($link_id);
        $this->assertSame($is_hidden, $link->is_hidden);
    }

    public function testUpdateDoesNotRemoveFromBookmarksNewsOrReadList()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_to_bookmarks = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $link_to_read_list = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNotNull($link_to_collection);
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news));
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list));
    }

    public function testUpdateCreatesComment()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $comment = $this->fake('sentence');

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
            'comment' => $comment,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\Message::count());
        $message = models\Message::take();
        $this->assertSame($comment, $message->content);
        $this->assertSame($user->id, $message->user_id);
        $this->assertSame($link_id, $message->link_id);
    }

    public function testUpdateCanMarkAsRead()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertFalse($exists_in_bookmarks);
        $this->assertFalse($exists_in_news);
        $this->assertNotNull($link_to_read_list);
    }

    public function testUpdateCopiesNotOwnedAndAccessibleLinks()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $other_collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $owned_collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $other_collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $other_collection_id]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => $from,
            'collection_ids' => [$owned_collection_id],
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link didn't change of collections
        $link_to_other_collection = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $other_collection_id,
        ]);
        $link_to_owned_collection = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $owned_collection_id,
        ]);
        $this->assertNotNull($link_to_other_collection);
        $this->assertNull($link_to_owned_collection);
        // But a new link exists, attached to the owned collection
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->via_type);
        $this->assertSame($other_collection_id, $new_link->via_resource_id);
        $new_link_to_other_collection = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $other_collection_id,
        ]);
        $new_link_to_owned_collection = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $owned_collection_id,
        ]);
        $this->assertNull($new_link_to_other_collection);
        $this->assertNotNull($new_link_to_owned_collection);
    }

    public function testUpdateWorksIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testUpdateCanCreateCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(1, models\Collection::count());

        $link = models\Link::find($link_id);
        $collection = models\Collection::findBy([
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertNotNull($collection);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
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
            'csrf' => \Minz\CSRF::generate(),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfLinkIsNotAccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 1,
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
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponseCode($response, 404);
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id_2,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfCollectionIdsContainsNotOwnedId()
    {
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
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCollectionIsSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfNewCollectionNameIsInvalid()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 100, true);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('errors', [
            'name' => 'The name must be less than 100 characters.',
        ]);
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCsrfIsInvalid()
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
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertSame(0, models\LinkToCollection::count());
    }
}
