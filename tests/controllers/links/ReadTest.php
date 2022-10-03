<?php

namespace flusio\controllers\links;

use flusio\models;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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

    public function testCreateWorksIfNotOwnedAndNotHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $read_list = $user->readList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'is_hidden' => 0,
            'url' => $url,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $news->id]);

        $response = $this->appRun('post', "/links/{$link_id}/read", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link is not modified since it's not owned by the logged
        // user.
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        // But the logged user now has a new link in its own read list
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->via_type);
        $this->assertSame($news->id, $new_link->via_resource_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
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

    public function testCreateFailsIfNotOwnedAndHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'is_hidden' => 1,
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

    public function testLaterWorksIfNotOwnedAndNotHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $news = $other_user->news();
        $bookmarks = $user->bookmarks();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'is_hidden' => 0,
            'url' => $url,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $news->id]);

        $response = $this->appRun('post', "/links/{$link_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link is not modified since it's not owned by the logged
        // user.
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        // But the logged user now has a new link in its own bookmarks
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->via_type);
        $this->assertSame($news->id, $new_link->via_resource_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
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

    public function testLaterFailsIfNotOwnedAndHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $news = $other_user->news();
        $bookmarks = $user->bookmarks();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'is_hidden' => 1,
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

    public function testNeverMarksToNeverRead()
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $never_list = $user->neverList();
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

        $response = $this->appRun('post', "/links/{$link_id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news_id));
        $this->assertFalse(models\LinkToCollection::exists($link_to_bookmarks_id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNotNull($link_to_never_list);
    }

    public function testNeverWorksIfNotOwnedAndNotHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $news = $other_user->news();
        $bookmarks = $other_user->bookmarks();
        $never_list = $user->neverList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'is_hidden' => 0,
            'url' => $url,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        // The initial link is not modified since it's not owned by the logged
        // user.
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));

        // But the logged user now has a new link in its own read list
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNotNull($link_to_never_list);
    }

    public function testNeverRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $never_list = $user->neverList();
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

        $response = $this->appRun('post', "/links/{$link_id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNull($link_to_never_list);
    }

    public function testNeverFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $never_list = $user->neverList();
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

        $response = $this->appRun('post', "/links/{$link_id}/read/never", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNull($link_to_never_list);
    }

    public function testNeverFailsIfNotOwnedAndHidden()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $news = $other_user->news();
        $bookmarks = $other_user->bookmarks();
        $never_list = $user->neverList();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'is_hidden' => 1,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_news_id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks_id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNull($link_to_never_list);
    }

    public function testDeleteMarksAsUnread()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_read_list_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_read_list_id));
    }

    public function testDeleteRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_read_list_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list_id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_read_list_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/delete", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list_id));
    }

    public function testDeleteFailsIfNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $read_list = $other_user->readList();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
        ]);
        $link_to_read_list_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/read/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list_id));
    }
}
