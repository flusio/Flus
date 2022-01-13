<?php

namespace flusio\controllers\collections;

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

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('get', '/read');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'read/index.phtml');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('get', '/read');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fread');
    }

    public function testIndexRedirectsIfPageOutOfBound()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('get', '/read', [
            'page' => 2,
        ]);

        $this->assertResponseCode($response, 302, '/read?page=1');
    }

    public function testCreateMarksLinksAsReadAndRedirects()
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

        $response = $this->appRun('post', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
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

        $response = $this->appRun('post', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $this->assertFalse($exists_in_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testCreateMarksLinksAsReadFromFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $read_list = $user->readList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
            'is_public' => 1,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponse($response, 302, '/news');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list, 'The link should be in read list.');
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

        $response = $this->appRun('post', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
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

    public function testCreateFailsIfCollectionIsInaccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $news = $other_user->news();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponse($response, 404);
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

        $response = $this->appRun('post', "/collections/{$news->id}/read", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('news'),
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
    }

    public function testLaterMarksLinksToReadLaterFromFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $user->bookmarks();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
            'is_public' => 1,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponse($response, 302, '/news');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks, 'The link should be in the bookmarks.');
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
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

    public function testLaterFailsIfCollectionIsInaccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $user->bookmarks();
        $news = $other_user->news();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('post', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponse($response, 404);
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/later", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('news'),
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

    public function testNeverMarksNewsLinksToNeverReadAndRedirects()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertFalse($exists_in_bookmarks, 'The link should no longer be in bookmarks.');
        $this->assertNotNull($link_to_never_list, 'The link should be in the never list.');
    }

    public function testNeverMarksLinksToNeverReadFromFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $never_list = $user->neverList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
            'is_public' => 1,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponse($response, 302, '/news');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNotNull($link_to_never_list, 'The link should be in the never list.');
    }

    public function testNeverRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
        $this->assertNull($link_to_never_list, 'The link should not be in the never list.');
    }

    public function testNeverFailsIfCollectionIsInaccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $never_list = $user->neverList();
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
        ]);
        $link_to_news_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('post', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 404);
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
        $this->assertNull($link_to_never_list, 'The link should not be in the never list.');
    }

    public function testNeverFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('post', "/collections/{$news->id}/read/never", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $exists_in_news = models\LinkToCollection::exists($link_to_news_id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks_id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
        $this->assertNull($link_to_never_list, 'The link should not be in the never list.');
    }
}
