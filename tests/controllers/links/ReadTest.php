<?php

namespace App\controllers\links;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testCreateMarksAsRead(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertFalse(models\LinkToCollection::exists($link_to_news->id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list);
    }

    public function testCreateWorksEvenIfNotInBookmarks(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\LinkToCollection::count());
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list);
    }

    public function testCreateWorksIfNotOwnedAndNotHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $read_list = $user->readList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $news->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link is not modified since it's not owned by the logged
        // user.
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        // But the logged user now has a new link in its own read list
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($news->id, $new_link->source_resource_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list);
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNull($link_to_read_list);
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNull($link_to_read_list);
    }

    public function testCreateFailsIfNotOwnedAndHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNull($link_to_read_list);
    }

    public function testLaterMarksToBeReadLater(): void
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news->id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks);
    }

    public function testLaterWorksEvenIfNotInNews(): void
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\LinkToCollection::count());
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks);
    }

    public function testLaterWorksIfNotOwnedAndNotHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $news = $other_user->news();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $news->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link is not modified since it's not owned by the logged
        // user.
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        // But the logged user now has a new link in its own bookmarks
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($news->id, $new_link->source_resource_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks);
    }

    public function testLaterRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_bookmarks);
    }

    public function testLaterFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_bookmarks);
    }

    public function testLaterFailsIfNotOwnedAndHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $news = $other_user->news();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_bookmarks);
    }

    public function testNeverMarksToNeverRead(): void
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news->id));
        $this->assertFalse(models\LinkToCollection::exists($link_to_bookmarks->id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNotNull($link_to_never_list);
    }

    public function testNeverWorksIfNotOwnedAndNotHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $news = $other_user->news();
        $bookmarks = $other_user->bookmarks();
        $never_list = $user->neverList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        // The initial link is not modified since it's not owned by the logged
        // user.
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));

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

    public function testNeverRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNull($link_to_never_list);
    }

    public function testNeverFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNull($link_to_never_list);
    }

    public function testNeverFailsIfNotOwnedAndHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $news = $other_user->news();
        $bookmarks = $other_user->bookmarks();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNull($link_to_never_list);
    }

    public function testDeleteMarksAsUnread(): void
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_read = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\LinkToCollection::exists($link_to_read->id));
    }

    public function testDeleteRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_read = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $this->assertTrue(models\LinkToCollection::exists($link_to_read->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_read = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\LinkToCollection::exists($link_to_read->id));
    }

    public function testDeleteFailsIfNotOwned(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $read_list = $other_user->readList();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_read = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\LinkToCollection::exists($link_to_read->id));
    }
}
