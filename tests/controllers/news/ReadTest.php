<?php

namespace flusio\controllers\news;

use flusio\models;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testCreateMarksNewsLinksAsReadAndRedirects()
    {
        $user = $this->login();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', '/news/read', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertFalse($exists_in_news, 'The link should not be in news.');
        $this->assertNotNull($link_to_read_list, 'The link should be in read list.');
    }

    public function testCreateRemovesFromBookmarks()
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news/read', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $this->assertFalse($exists_in_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testCreateRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', '/news/read', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertNull($link_to_read_list, 'The link should not be in read list.');
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', '/news/read', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertNull($link_to_read_list, 'The link should not be in read list.');
    }

    public function testLaterMarksNewsLinksToReadLaterAndRedirects()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', '/news/read/later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertNotNull($link_to_bookmarks, 'The link should be in bookmarks.');
    }

    public function testLaterJustRemovesFromNewsIfAlreadyBookmarked()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', '/news/read/later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
    }

    public function testLaterRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', '/news/read/later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should still be in news.');
        $this->assertNull($link_to_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testLaterFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', '/news/read/later', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should still be in news.');
        $this->assertNull($link_to_bookmarks, 'The link should not be in bookmarks.');
    }
}
