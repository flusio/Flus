<?php

namespace App\controllers\collections;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testCreateMarksLinksAsReadAndRedirects(): void
    {
        $user = $this->login();
        $news = $user->news();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollections([$news, $bookmarks]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link->isReadBy($user), 'The link should be in read list.');
        $this->assertFalse($link->isInNewsOf($user), 'The link should not be in news.');
        $this->assertFalse($link->isInBookmarksOf($user), 'The link should not be in bookmarks.');
    }

    public function testCreateMarksLinksAsReadFromPublicCollection(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $public_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $hidden_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $public_link->addCollection($collection);
        $hidden_link->addCollection($collection);
        $referer = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $this->assertSame(1, models\Link::countBy(['user_id' => $user->id]));
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame($public_link->url, $new_link->url);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($collection->id, $new_link->source_resource_id);
        $this->assertTrue($new_link->isReadBy($user), 'The link should be in read list.');
    }

    public function testCreateMarksHiddenLinksAsReadIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $link->addCollection($collection);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertTrue($new_link->isReadBy($user), 'The link should be in read list.');
    }

    public function testCreateMarksLinksAsReadForSpecificDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news, at: new \DateTimeImmutable('2024-03-25'));
        $link2->addCollection($news, at: new \DateTimeImmutable('2024-03-26'));

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link1->isReadBy($user), 'The link should be in read list.');
        $this->assertFalse($link1->isInNewsOf($user), 'The link should not be in news.');
        $this->assertFalse($link2->isReadBy($user), 'The link should not be in read list.');
        $this->assertTrue($link2->isInNewsOf($user), 'The link should be in news.');
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
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source2->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
            'source' => "collection#{$source1->id}",
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link1->isReadBy($user), 'The link should be in read list.');
        $this->assertFalse($link1->isInNewsOf($user), 'The link should not be in news.');
        $this->assertFalse($link2->isReadBy($user), 'The link should not be in read list.');
        $this->assertTrue($link2->isInNewsOf($user), 'The link should be in news.');
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($link->isReadBy($user), 'The link should not be in read list.');
        $this->assertTrue($link->isInNewsOf($user), 'The link should be in news.');
    }

    public function testCreateFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($link->isReadBy($user), 'The link should not be in read list.');
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($link->isReadBy($user), 'The link should not be in read list.');
        $this->assertTrue($link->isInNewsOf($user), 'The link should be in news.');
    }

    public function testLaterMarksNewsLinksToReadLaterAndRedirects(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link->isInBookmarksOf($user), 'The link should be in bookmarks.');
        $this->assertFalse($link->isInNewsOf($user), 'The link should not be in news.');
    }

    public function testLaterMarksLinksToReadLaterFromPublicCollection(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $public_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $hidden_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $public_link->addCollection($collection);
        $hidden_link->addCollection($collection);
        $referer = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $this->assertSame(1, models\Link::countBy(['user_id' => $user->id]));
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($collection->id, $new_link->source_resource_id);
        $this->assertTrue($new_link->isInBookmarksOf($user), 'The link should be in bookmarks.');
    }

    public function testLaterMarksHiddenLinksToReadLaterIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $link->addCollection($collection);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertTrue($new_link->isInBookmarksOf($user), 'The link should be in bookmarks.');
    }

    public function testLaterMarksNewsLinksToReadLaterForSpecificDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news, at: new \DateTimeImmutable('2024-03-25'));
        $link2->addCollection($news, at: new \DateTimeImmutable('2024-03-26'));

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link1->isInBookmarksOf($user), 'The link should be in bookmarks.');
        $this->assertFalse($link2->isInBookmarksOf($user), 'The link should not be in bookmarks.');
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
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source2->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
            'source' => "collection#{$source1->id}",
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link1->isInBookmarksOf($user), 'The link should be in bookmarks.');
        $this->assertFalse($link2->isInBookmarksOf($user), 'The link should not be in bookmarks.');
    }

    public function testLaterRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($link->isInBookmarksOf($user), 'The link should not be in bookmarks.');
        $this->assertTrue($link->isInNewsOf($user), 'The link should be in news.');
    }

    public function testLaterFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($link->isInBookmarksOf($user), 'The link should not be in bookmarks.');
    }

    public function testLaterFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($link->isInBookmarksOf($user), 'The link should not be in bookmarks.');
    }

    public function testNeverMarksNewsLinksToNeverReadAndRedirects(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollections([$news, $bookmarks]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link->isInNeverList($user), 'The link should be in never list.');
        $this->assertFalse($link->isInNewsOf($user), 'The link should not be in news.');
        $this->assertFalse($link->isInBookmarksOf($user), 'The link should not be in bookmarks.');
    }

    public function testNeverMarksLinksToNeverReadFromPublicCollection(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $public_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $hidden_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $public_link->addCollection($collection);
        $hidden_link->addCollection($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(1, models\Link::countBy(['user_id' => $user->id]));
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame($public_link->url, $new_link->url);
        $this->assertTrue($new_link->isInNeverList($user), 'The link should be in never list.');
    }

    public function testNeverMarksHiddenLinksToNeverReadIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $link->addCollection($collection);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertTrue($new_link->isInNeverList($user), 'The link should be in never list.');
    }

    public function testNeverMarksNewsLinksToNeverReadForSpecificDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news, at: new \DateTimeImmutable('2024-03-25'));
        $link2->addCollection($news, at: new \DateTimeImmutable('2024-03-26'));

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link1->isInNeverList($user), 'The link should be in never list.');
        $this->assertFalse($link2->isInNeverList($user), 'The link should not be in never list.');
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
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $source2->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
            'source' => "collection#{$source1->id}",
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($link1->isInNeverList($user), 'The link should be in never list.');
        $this->assertFalse($link2->isInNeverList($user), 'The link should not be in never list.');
    }

    public function testNeverRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($link->isInNeverList($user), 'The link should not be in never list.');
        $this->assertTrue($link->isInNewsOf($user), 'The link should be in news.');
    }

    public function testNeverFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($link->isInNeverList($user), 'The link should not be in never list.');
    }

    public function testNeverFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link->addCollection($news);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($link->isInNeverList($user), 'The link should not be in never list.');
        $this->assertTrue($link->isInNewsOf($user), 'The link should be in news.');
    }
}
