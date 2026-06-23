<?php

namespace App\controllers\collections;

use App\forms;
use App\models;
use App\utils;
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
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsReadLater($link);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
        $this->assertFalse($news->hasLink($link), 'The link should not be in news.');
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
        $collection->addLinks([$public_link, $hidden_link]);
        $referer = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $links = models\Link::listBy([
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, count($links));
        $new_link = $links[0];
        $this->assertSame($public_link->url, $new_link->url);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        $this->assertSame($origin, $new_link->origin);
        $this->assertTrue($user->hasRead($new_link));
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
        $collection->addLinks([$link]);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]));
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
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
        $news->addLinks([$link1], at: new \DateTimeImmutable('2024-03-25'));
        $news->addLinks([$link2], at: new \DateTimeImmutable('2024-03-26'));

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertFalse($news->hasLink($link1), 'The link should not be in news.');
        $this->assertFalse($user->hasRead($link2), 'The link should not be read.');
        $this->assertTrue($news->hasLink($link2), 'The link should be in news.');
    }

    public function testCreateMarksLinksAsReadForSpecificOrigin(): void
    {
        $user = $this->login();
        $news = $user->news();
        $collection1 = CollectionFactory::create();
        $collection2 = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection1->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection2->id,
        ]);
        $news->addLinks([$link1, $link2]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
            'source' => $collection1->id,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertFalse($news->hasLink($link1), 'The link should not be in news.');
        $this->assertFalse($user->hasRead($link2), 'The link should not be read.');
        $this->assertTrue($news->hasLink($link2), 'The link should be in news.');
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
        $this->assertTrue($news->hasLink($link), 'The link should be in news.');
    }

    public function testCreateFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsRead::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
        $this->assertTrue($news->hasLink($link), 'The link should be in news.');
    }

    public function testLaterMarksNewsLinksToReadLaterAndRedirects(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link), 'The link should be to read later.');
        $this->assertFalse($news->hasLink($link), 'The link should not be in news.');
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
        $collection->addLinks([$public_link, $hidden_link]);
        $referer = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $links = models\Link::listBy([
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, count($links));
        $new_link = $links[0];
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        $this->assertSame($origin, $new_link->origin);
        $this->assertTrue($user->hasReadLater($new_link), 'The link should be to read later.');
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
        $collection->addLinks([$link]);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]));
        $this->assertTrue($user->hasReadLater($link), 'The link should be to read later.');
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
        $news->addLinks([$link1], at: new \DateTimeImmutable('2024-03-25'));
        $news->addLinks([$link2], at: new \DateTimeImmutable('2024-03-26'));

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link1), 'The link should be to read later.');
        $this->assertFalse($news->hasLink($link1), 'The link should not be in news.');
        $this->assertFalse($user->hasReadLater($link2), 'The link should not be to read later.');
        $this->assertTrue($news->hasLink($link2), 'The link should be in news.');
    }

    public function testLaterMarksNewsLinksToReadLaterForSpecificOrigin(): void
    {
        $user = $this->login();
        $news = $user->news();
        $collection1 = CollectionFactory::create();
        $collection2 = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection1->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection2->id,
        ]);
        $news->addLinks([$link1, $link2]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
            'source' => $collection1->id,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link1), 'The link should be to read later.');
        $this->assertFalse($news->hasLink($link1), 'The link should not be in news.');
        $this->assertFalse($user->hasReadLater($link2), 'The link should not be to read later.');
        $this->assertTrue($news->hasLink($link2), 'The link should be in news.');
    }

    public function testLaterRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
        $this->assertTrue($news->hasLink($link), 'The link should be in news.');
    }

    public function testLaterFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
    }

    public function testLaterFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/later", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
        $this->assertTrue($news->hasLink($link), 'The link should be in news.');
    }

    public function testNeverMarksNewsLinksToBeDismissedAndRedirects(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link), 'The link should be has been dismissed.');
        $this->assertFalse($news->hasLink($link), 'The link should not be in news.');
    }

    public function testNeverMarksLinksToBeDismissedFromPublicCollection(): void
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
        $collection->addLinks([$public_link, $hidden_link]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($public_link), 'The link should has been dismissed.');
        $this->assertFalse($user->hasDismissed($hidden_link), 'The link should not has been dismissed.');
        $this->assertSame(0, models\Link::countBy([
            'user_id' => $user->id,
        ]));
    }

    public function testNeverMarksHiddenLinksToBeDismissedIfCollectionIsShared(): void
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
        $collection->addLinks([$link]);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link), 'The link should has been dismissed.');
        // Link is not copied in case of dismissing.
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]));
    }

    public function testNeverMarksNewsLinksToBeDismissedForSpecificDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link1], at: new \DateTimeImmutable('2024-03-25'));
        $news->addLinks([$link2], at: new \DateTimeImmutable('2024-03-26'));

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
            'date' => '2024-03-25',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link1), 'The link should has been dismissed.');
        $this->assertFalse($news->hasLink($link1), 'The link should not be in news.');
        $this->assertFalse($user->hasDismissed($link2), 'The link should not has been dismissed.');
        $this->assertTrue($news->hasLink($link2), 'The link should be in news.');
    }

    public function testNeverMarksNewsLinksToBeDismissedForSpecificOrigin(): void
    {
        $user = $this->login();
        $news = $user->news();
        $collection1 = CollectionFactory::create();
        $collection2 = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection1->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection2->id,
        ]);
        $news->addLinks([$link1, $link2]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
            'source' => $collection1->id,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link1), 'The link should has been dismissed.');
        $this->assertFalse($news->hasLink($link1), 'The link should not be in news.');
        $this->assertFalse($user->hasDismissed($link2), 'The link should not has been dismissed.');
        $this->assertTrue($news->hasLink($link2), 'The link should be in news.');
    }

    public function testNeverRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasDismissed($link), 'The link should not has been dismissed.');
        $this->assertTrue($news->hasLink($link), 'The link should be in news.');
    }

    public function testNeverFailsIfCollectionIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\collections\MarkCollectionAsNever::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasDismissed($link), 'The link should not has been dismissed.');
    }

    public function testNeverFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/collections/{$news->id}/read/never", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasDismissed($link), 'The link should not has been dismissed.');
        $this->assertTrue($news->hasLink($link), 'The link should be in news.');
    }
}
