<?php

namespace flusio\controllers\links;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_3 = CollectionFactory::create([
            'user_id' => $user->id,
            'name' => $collection_name,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
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
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Store the link and mark as read');
    }

    public function testIndexRendersExistingLink()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = $this->fake('url');
        $link_not_owned = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $link_owned = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_not_owned = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        $collection_owned = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_not_owned->id,
            'collection_id' => $collection_not_owned->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_owned->id,
            'collection_id' => $collection_owned->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link_not_owned->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/collections/index.phtml');
        $this->assertResponseContains($response, $link_owned->id);
        $this->assertResponseNotContains($response, $link_not_owned->id);
    }

    public function testIndexRendersCorrectLinkIfDuplicated()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link_2->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/collections/index.phtml');
        $this->assertResponseContains($response, $link_2->id);
        $this->assertResponseNotContains($response, $link_1->id);
    }

    public function testIndexRendersIfUrlAddedByAnotherUserInCollectionOwned()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $other_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $other_link->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $other_user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $other_link->id);
    }

    public function testIndexRendersIfUrlAddedByOwnerInCollectionWithWriteAccess()
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
        ]);
        $owner_link = LinkFactory::create([
            'user_id' => $owner->id,
            'url' => $url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $owner_link->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $owner_link->id);
    }

    public function testIndexRendersCollectionSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection->id);
    }

    public function testIndexDoesNotCopyNotOwnedAndAccessibleLinks()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection_name = $this->fake('words', 3, true);
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
            'name' => $collection_name,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
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
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection->id);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user = UserFactory::create();
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
    }

    public function testIndexFailsIfLinkIsNotAccessible()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesCollectionsAndRedirects()
    {
        $user = $this->login();
        $is_hidden = $this->fake('boolean');
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_2->id],
            'is_hidden' => $is_hidden,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_2->id,
        ]);
        $this->assertNull($link_to_collection_1);
        $this->assertNotNull($link_to_collection_2);
        $link = $link->reload();
        $this->assertSame($is_hidden, $link->is_hidden);
    }

    public function testUpdateDoesNotRemoveFromBookmarksNewsOrReadList()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_read_list = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list->id));
    }

    public function testUpdateCreatesComment()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $comment = $this->fake('sentence');

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
            'comment' => $comment,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\Message::count());
        $message = models\Message::take();
        $this->assertSame($comment, $message->content);
        $this->assertSame($user->id, $message->user_id);
        $this->assertSame($link->id, $message->link_id);
    }

    public function testUpdateCanMarkAsRead()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertFalse($exists_in_bookmarks);
        $this->assertFalse($exists_in_news);
        $this->assertNotNull($link_to_read_list);
    }

    public function testUpdateCopiesNotOwnedAndAccessibleLinks()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $other_collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $owned_collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $other_collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $other_collection->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => $from,
            'collection_ids' => [$owned_collection->id],
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link didn't change of collections
        $link_to_other_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $other_collection->id,
        ]);
        $link_to_owned_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $owned_collection->id,
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
        $this->assertSame($other_collection->id, $new_link->via_resource_id);
        $new_link_to_other_collection = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $other_collection->id,
        ]);
        $new_link_to_owned_collection = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $owned_collection->id,
        ]);
        $this->assertNull($new_link_to_other_collection);
        $this->assertNotNull($new_link_to_owned_collection);
    }

    public function testUpdateWorksIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testUpdateCanCreateCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(1, models\Collection::count());

        $link = $link->reload();
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
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => \Minz\Csrf::generate(),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_2->id],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_2->id,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfLinkIsNotAccessible()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'bookmarks',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_2->id],
        ]);

        $this->assertResponseCode($response, 404);
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_2->id,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfCollectionIdsContainsNotOwnedId()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('One of the associated collection doesn’t exist.', \Minz\Flash::get('error'));
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('One of the associated collection doesn’t exist.', \Minz\Flash::get('error'));
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCollectionIsSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('One of the associated collection doesn’t exist.', \Minz\Flash::get('error'));
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfNewCollectionNameIsInvalid()
    {
        $user = $this->login();
        $collection_name = $this->fake('words', 100, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertEquals([
            'name' => 'The name must be less than 100 characters.',
        ], \Minz\Flash::get('errors'));
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertSame(0, models\LinkToCollection::count());
    }
}
