<?php

namespace flusio\controllers\links;

use flusio\models;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testCreateMarksAsRead()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_bookmarks_id));
        $this->assertFalse(models\LinkToCollection::exists($link_to_news_id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list);
    }

    public function testCreateWorksEvenIfNotInBookmarks()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\LinkToCollection::count());
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list);
    }

    public function testCreateRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNull($link_to_read_list);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNull($link_to_read_list);
    }

    public function testCreateFailsIfNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNull($link_to_read_list);
    }

    public function testLaterMarksToBeReadLater()
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

        $response = $this->appRun('post', "/links/{$link_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news_id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks);
    }

    public function testLaterWorksEvenIfNotInNews()
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\LinkToCollection::count());
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks);
    }

    public function testLaterRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_bookmarks);
    }

    public function testLaterFailsIfCsrfIsInvalid()
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

        $response = $this->appRun('post', "/links/{$link_id}/read/later", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_bookmarks);
    }

    public function testLaterFailsIfNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $news = $other_user->news();
        $bookmarks = $user->bookmarks();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_bookmarks);
    }
}
