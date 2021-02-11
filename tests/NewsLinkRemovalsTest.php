<?php

namespace flusio;

class NewsLinkRemovalsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testAddingRendersCorrectly()
    {
        $user = $this->login();
        $title = $this->fake('sentence');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'news_link_removals/adding.phtml');
    }

    public function testAddingAdaptsSubmitButtonIfTheLinkIsAlreadyPartOfCollections()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 200, 'Save and mark as read');
    }

    public function testAddingRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $title = $this->fake('sentence');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fnews%2F{$news_link_id}%2Fadd");
    }

    public function testAddingFailsIfLinkIsNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $title = $this->fake('sentence');
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/news/{$news_link_id}/add");

        $this->assertResponse($response, 404);
    }

    public function testAddCreatesALinkAndRedirects()
    {
        $user = $this->login();
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'is_read' => 0,
            'is_removed' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertSame(1, models\Link::count());

        $this->assertResponse($response, 302, '/news');
        $link = models\Link::take();
        $news_link = models\NewsLink::find($news_link_id);
        $message = $link->messages()[0];
        $db_link_to_collection = $links_to_collections_dao->listAll()[0];
        $this->assertTrue($news_link->is_read);
        $this->assertFalse($news_link->is_removed);
        $this->assertSame($user->id, $link->user_id);
        $this->assertSame($news_link->title, $link->title);
        $this->assertSame($news_link->url, $link->url);
        $this->assertTrue($link->is_public);
        $this->assertSame($comment, $message->content);
        $this->assertSame($link->id, $db_link_to_collection['link_id']);
        $this->assertSame($collection_id, $db_link_to_collection['collection_id']);
    }

    public function testAddUpdatesExistingLinks()
    {
        $user = $this->login();
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $url = $this->fake('url');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'is_public' => 0,
        ]);
        $old_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $new_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $old_collection_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$new_collection_id];

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertSame(1, models\Link::count());

        $this->assertResponse($response, 302, '/news');
        $link = models\Link::find($link_id);
        $this->assertTrue($link->is_public);
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
        $new_db_link_to_collection = $links_to_collections_dao->listAll()[0];
        $this->assertSame($link_id, $new_db_link_to_collection['link_id']);
        $this->assertSame($new_collection_id, $new_db_link_to_collection['collection_id']);
    }

    public function testAddRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => 'a token',
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fnews%2F{$news_link_id}%2Fadd");
        $this->assertSame(0, models\Link::count());
    }

    public function testAddFailsIfUserDoesNotOwnTheNewsLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, models\Link::count());
    }

    public function testAddFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => 'not the token',
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testAddFailsIfCollectionIdsIsEmpty()
    {
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 400, 'The link must be associated to a collection.');
        $this->assertSame(0, models\Link::count());
    }

    public function testAddFailsIfCollectionIdsContainsNotOwnedId()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $is_public = true;
        $comment = $this->fake('sentence');
        $collection_ids = [$collection_id];

        $response = $this->appRun('post', "/news/{$news_link_id}/add", [
            'csrf' => $user->csrf,
            'is_public' => $is_public,
            'collection_ids' => $collection_ids,
            'comment' => $comment,
        ]);

        $this->assertResponse($response, 400, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\Link::count());
    }

    public function testReadLaterRemovesLinkFromNewsAndAddsToBookmarksAndRedirects()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists = models\NewsLink::exists($news_link_id);
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertFalse($exists, 'The news should no longer exist.');
        $this->assertNotNull($db_link_to_collection, 'The link should be in bookmarks.');
    }

    public function testReadLaterCreatesTheLinkIfItDoesNotExistForCurrentUser()
    {
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $link = models\Link::findBy(['url' => $link_url]);
        $this->assertNotNull($link, 'The link should exist.');
    }

    public function testReadLaterJustRemovesFromNewsIfAlreadyBookmarked()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists = models\NewsLink::exists($news_link_id);
        $db_link_to_collection = $links_to_collections_dao->find($link_to_collection_id);
        $this->assertFalse($exists, 'The news should no longer exist.');
        $this->assertNotNull($db_link_to_collection, 'The link should still be in bookmarks.');
    }

    public function testReadLaterAcceptsAnIdEqualsToAll()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_url_1 = $this->fake('url');
        $link_url_2 = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_1,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_2,
        ]);
        $news_link_id_1 = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url_1,
            'is_read' => 0,
            'is_removed' => 0,
        ]);
        $news_link_id_2 = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url_2,
            'is_read' => 0,
            'is_removed' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_2,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', '/news/all/read-later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists_1 = models\NewsLink::exists($news_link_id_1);
        $exists_2 = models\NewsLink::exists($news_link_id_2);
        $this->assertFalse($exists_1, 'The news should no longer exist.');
        $this->assertFalse($exists_2, 'The news should no longer exist.');
        $db_link_to_collection_1 = $links_to_collections_dao->findBy([
            'link_id' => $link_id_1,
            'collection_id' => $collection_id,
        ]);
        $db_link_to_collection_2 = $links_to_collections_dao->findBy([
            'link_id' => $link_id_2,
            'collection_id' => $collection_id,
        ]);
        $this->assertNotNull($db_link_to_collection_1, 'The link should be in bookmarks.');
        $this->assertNotNull($db_link_to_collection_2, 'The link should be in bookmarks.');
    }

    public function testReadLaterRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $exists = models\NewsLink::exists($news_link_id);
        $link = models\Link::findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $exists = models\NewsLink::exists($news_link_id);
        $link = models\Link::findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfLinkDoesNotExist()
    {
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', '/news/-1/read-later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $exists = models\NewsLink::exists($news_link_id);
        $link = models\Link::findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfUserDoesNotOwnTheLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $exists = models\NewsLink::exists($news_link_id);
        $link = models\Link::findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($link, 'The link should not exist.');
    }

    public function testMarkAsReadMarksLinkAsReadAndRedirects()
    {
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertTrue($news_link->is_read, 'The news link should be read.');
    }

    public function testMarkAsReadRemovesFromBookmarksIfCorrespondingUrlInLinks()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $exists_in_bookmarks = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($exists_in_bookmarks, 'The link should no longer be in bookmarks.');
    }

    public function testMarkAsReadAcceptsAnIdEqualsToAll()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id_1 = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_read' => 0,
            'is_removed' => 0,
        ]);
        $news_link_id_2 = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', '/news/all/mark-as-read', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link_1 = models\NewsLink::find($news_link_id_1);
        $news_link_2 = models\NewsLink::find($news_link_id_2);
        $this->assertTrue($news_link_1->is_read, 'The news link should be read.');
        $this->assertTrue($news_link_2->is_read, 'The news link should be read.');
        $exists_in_bookmarks = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($exists_in_bookmarks, 'The link should no longer be in bookmarks.');
    }

    public function testMarkAsReadRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $this->fake('url'),
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/mark-as-read", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertFalse($news_link->is_read, 'The news link should not be read.');
    }

    public function testMarkAsReadFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/mark-as-read", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertFalse($news_link->is_read, 'The news link should not be read.');
    }

    public function testMarkAsReadFailsIfUserDoesNotOwnTheLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $this->fake('url'),
            'is_read' => 0,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/mark-as-read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertFalse($news_link->is_read, 'The news link should not be read.');
    }

    public function testRemoveRemovesLinkFromNewsAndRedirects()
    {
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/remove", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertTrue($news_link->is_removed, 'The news link should be removed.');
    }

    public function testRemoveRemovesFromBookmarksIfCorrespondingUrlInLinks()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/remove", [
            'csrf' => $user->csrf,
        ]);

        $exists_in_bookmarks = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($exists_in_bookmarks, 'The link should no longer be in bookmarks.');
    }

    public function testRemoveRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $this->fake('url'),
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/remove", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertFalse($news_link->is_removed, 'The news link should not be removed.');
    }

    public function testRemoveFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $this->fake('url'),
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/remove", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertFalse($news_link->is_removed, 'The news link should not be removed.');
    }

    public function testRemoveFailsIfUserDoesNotOwnTheLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $this->fake('url'),
            'is_removed' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/remove", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $news_link = models\NewsLink::find($news_link_id);
        $this->assertFalse($news_link->is_removed, 'The news link should not be removed.');
    }
}
