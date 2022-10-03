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

        $this->assertResponseCode($response, 302, '/news');
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

        $this->assertResponseCode($response, 302, '/news');
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->via_type);
        $this->assertSame($collection_id, $new_link->via_resource_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list, 'The link should be in read list.');
    }

    public function testCreateMarksHiddenLinksAsReadFromFollowedIfCollectionIsShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $read_list = $user->readList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
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

    public function testCreateDoesNotMarkHiddenLinksAsReadFromFollowedIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $read_list = $user->readList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
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

        $this->assertResponseCode($response, 302, '/news');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($new_link);
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

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
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

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 302, '/news');
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->via_type);
        $this->assertSame($collection_id, $new_link->via_resource_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks, 'The link should be in the bookmarks.');
    }

    public function testLaterMarksHiddenLinksToReadLaterFromFollowedIfCollectionIsShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $user->bookmarks();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
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

    public function testLaterDoesNotMarkHiddenLinksToReadLaterFromFollowedIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $bookmarks = $user->bookmarks();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
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

        $this->assertResponseCode($response, 302, '/news');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($new_link);
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

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 302, '/news');
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

        $this->assertResponseCode($response, 302, '/news');
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

    public function testNeverMarksHiddenLinksToNeverReadFromFollowedIfCollectionIsShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $never_list = $user->neverList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
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

    public function testNeverDoesNotMarkHiddenLinksToNeverReadFromFollowedIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $never_list = $user->neverList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => 1,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
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

        $this->assertResponseCode($response, 302, '/news');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($new_link);
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
