<?php

namespace App\controllers\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testCreateMarksLinksAsReadAndRedirects(): void
    {
        $user = $this->login();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertFalse($exists_in_news, 'The link should not be in news.');
        $this->assertNotNull($link_to_read_list, 'The link should be in read list.');
    }

    public function testCreateRemovesFromBookmarks(): void
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
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $this->assertFalse($exists_in_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testCreateMarksLinksAsReadFromFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $read_list = $user->readList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($collection->id, $new_link->source_resource_id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertNotNull($link_to_read_list, 'The link should be in read list.');
    }

    public function testCreateMarksHiddenLinksAsReadFromFollowedIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $read_list = $user->readList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
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

    public function testCreateDoesNotMarkHiddenLinksAsReadFromFollowedIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $read_list = $user->readList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
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

    public function testCreateMarksLinksAsReadForSpecificDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news1 = LinkToCollectionFactory::create([
            'link_id' => $link1->id,
            'collection_id' => $news->id,
            'created_at' => new \DateTimeImmutable('2024-03-25'),
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news2 = LinkToCollectionFactory::create([
            'link_id' => $link2->id,
            'collection_id' => $news->id,
            'created_at' => new \DateTimeImmutable('2024-03-26'),
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news1->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news2->id));
    }

    public function testCreateMarksLinksAsReadForSpecificSource(): void
    {
        $user = $this->login();
        $news = $user->news();
        $source1 = CollectionFactory::create();
        $source2 = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source1->id,
        ]);
        $link_to_news1 = LinkToCollectionFactory::create([
            'link_id' => $link1->id,
            'collection_id' => $news->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source2->id,
        ]);
        $link_to_news2 = LinkToCollectionFactory::create([
            'link_id' => $link2->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
            'source' => "collection#{$source1->id}",
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news1->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news2->id));
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertNull($link_to_read_list, 'The link should not be in read list.');
    }

    public function testCreateFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $news = $other_user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 404);
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertNull($link_to_read_list, 'The link should not be in read list.');
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertNull($link_to_read_list, 'The link should not be in read list.');
    }

    public function testLaterMarksNewsLinksToReadLaterAndRedirects(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertNotNull($link_to_bookmarks, 'The link should be in bookmarks.');
    }

    public function testLaterJustRemovesFromNewsIfAlreadyBookmarked(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
    }

    public function testLaterMarksLinksToReadLaterFromFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($collection->id, $new_link->source_resource_id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_bookmarks, 'The link should be in the bookmarks.');
    }

    public function testLaterMarksHiddenLinksToReadLaterFromFollowedIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
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

    public function testLaterDoesNotMarkHiddenLinksToReadLaterFromFollowedIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
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

    public function testLaterMarksNewsLinksToReadLaterForSpecificDate(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news1 = LinkToCollectionFactory::create([
            'link_id' => $link1->id,
            'collection_id' => $news->id,
            'created_at' => new \DateTimeImmutable('2024-03-25'),
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news2 = LinkToCollectionFactory::create([
            'link_id' => $link2->id,
            'collection_id' => $news->id,
            'created_at' => new \DateTimeImmutable('2024-03-26'),
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news1->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news2->id));
    }

    public function testLaterMarksNewsLinksToReadLaterForSpecificSource(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $source1 = CollectionFactory::create();
        $source2 = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source1->id,
        ]);
        $link_to_news1 = LinkToCollectionFactory::create([
            'link_id' => $link1->id,
            'collection_id' => $news->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source2->id,
        ]);
        $link_to_news2 = LinkToCollectionFactory::create([
            'link_id' => $link2->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
            'source' => "collection#{$source1->id}",
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news1->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news2->id));
    }

    public function testLaterRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should still be in news.');
        $this->assertNull($link_to_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testLaterFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        $news = $other_user->news();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 404);
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should still be in news.');
        $this->assertNull($link_to_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testLaterFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_bookmarks = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should still be in news.');
        $this->assertNull($link_to_bookmarks, 'The link should not be in bookmarks.');
    }

    public function testNeverMarksNewsLinksToNeverReadAndRedirects(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertFalse($exists_in_news, 'The link should no longer be in news.');
        $this->assertFalse($exists_in_bookmarks, 'The link should no longer be in bookmarks.');
        $this->assertNotNull($link_to_never_list, 'The link should be in the never list.');
    }

    public function testNeverMarksLinksToNeverReadFromFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $never_list = $user->neverList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
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

    public function testNeverMarksHiddenLinksToNeverReadFromFollowedIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $never_list = $user->neverList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
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

    public function testNeverDoesNotMarkHiddenLinksToNeverReadFromFollowedIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $never_list = $user->neverList();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
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

    public function testNeverMarksNewsLinksToNeverReadForSpecificDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news1 = LinkToCollectionFactory::create([
            'link_id' => $link1->id,
            'collection_id' => $news->id,
            'created_at' => new \DateTimeImmutable('2024-03-25'),
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_to_news2 = LinkToCollectionFactory::create([
            'link_id' => $link2->id,
            'collection_id' => $news->id,
            'created_at' => new \DateTimeImmutable('2024-03-26'),
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news1->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news2->id));
    }

    public function testNeverMarksNewsLinksToNeverReadForSpecificSource(): void
    {
        $user = $this->login();
        $news = $user->news();
        $source1 = CollectionFactory::create();
        $source2 = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source1->id,
        ]);
        $link_to_news1 = LinkToCollectionFactory::create([
            'link_id' => $link1->id,
            'collection_id' => $news->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source2->id,
        ]);
        $link_to_news2 = LinkToCollectionFactory::create([
            'link_id' => $link2->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
            'source' => "collection#{$source1->id}",
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\LinkToCollection::exists($link_to_news1->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news2->id));
    }

    public function testNeverRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fnews');
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
        $this->assertNull($link_to_never_list, 'The link should not be in the never list.');
    }

    public function testNeverFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $bookmarks = $other_user->bookmarks();
        $news = $other_user->news();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 404);
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
        $this->assertNull($link_to_never_list, 'The link should not be in the never list.');
    }

    public function testNeverFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
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

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('news'),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertTrue($exists_in_news, 'The link should be in news.');
        $this->assertTrue($exists_in_bookmarks, 'The link should be in bookmarks.');
        $this->assertNull($link_to_never_list, 'The link should not be in the never list.');
    }
}
