<?php

namespace flusio\controllers\links;

use flusio\models;

class CollectionsTest extends \PHPUnit\Framework\TestCase
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

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponse($response, 200, $collection_name);
        $this->assertPointer($response, 'links/collections/index.phtml');
    }

    public function testIndexRendersCorrectlyInModeNews()
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
            'mode' => 'news',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'What do you think?');
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

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarks');
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

        $response = $this->appRun('get', "/links/{$link_id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

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
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
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
    }

    public function testUpdateDoesNotRemoveFromNewsOrReadList()
    {
        $user = $this->login();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
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

        $this->assertResponse($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertNotNull($link_to_collection);
        $this->assertTrue(models\LinkToCollection::exists($link_to_news));
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list));
    }

    public function testUpdateInModeNewsAcceptsComment()
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
        $is_hidden = $this->fake('boolean');

        $response = $this->appRun('post', "/links/{$link_id}/collections", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id],
            'comment' => $comment,
            'is_hidden' => $is_hidden,
            'mode' => 'news',
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\Message::count());
        $message = models\Message::take();
        $this->assertSame($comment, $message->content);
        $this->assertSame($user->id, $message->user_id);
        $this->assertSame($link_id, $message->link_id);
        $link = models\Link::find($link_id);
        $this->assertSame($is_hidden, $link->is_hidden);
    }

    public function testUpdateInModeNewsMarksAsRead()
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
            'mode' => 'news',
        ]);

        $this->assertResponse($response, 302, '/bookmarks');
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

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarks');
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
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_id_2],
        ]);

        $this->assertResponse($response, 404);
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

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'One of the associated collection doesn’t exist.');
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

        $this->assertResponse($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertSame(0, models\LinkToCollection::count());
    }
}
