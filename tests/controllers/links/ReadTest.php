<?php

namespace App\controllers\links;

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

    public function testCreateMarksAsRead(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsReadLater($link);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->hasReadLater($link));
        $this->assertTrue($user->hasRead($link));
        $this->assertFalse($news->hasLink($link));
    }

    public function testCreateWorksIfNotOwnedAndNotHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $other_collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $other_user->markAsReadLater($link);
        $other_collection->addLinks([$link]);
        $from = \Minz\Url::for('collection', ['id' => $other_collection->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsRead::class),
        ], headers: [
            'Referer' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The read status didn't changed for the other user who owns the link.
        $this->assertTrue($other_user->hasReadLater($link));
        $this->assertFalse($other_user->hasRead($link));
        // But the logged user now has read the link and owns their own copy.
        $this->assertTrue($user->hasRead($link));
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertNotSame($link->id, $new_link->id);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $other_collection->id]);
        $this->assertSame($origin, $new_link->origin);
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsReadLater($link);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsRead::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($user->hasReadLater($link));
        $this->assertFalse($user->hasRead($link));
        $this->assertTrue($news->hasLink($link));
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsReadLater($link);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue($user->hasReadLater($link));
        $this->assertFalse($user->hasRead($link));
        $this->assertTrue($news->hasLink($link));
    }

    public function testCreateFailsIfNotOwnedAndHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsRead::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasRead($link));
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]));
    }

    public function testLaterMarksToBeReadLater(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link));
        $this->assertFalse($news->hasLink($link));
    }

    public function testLaterWorksIfNotOwnedAndNotHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $other_collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $other_collection->addLinks([$link]);
        $from = \Minz\Url::for('collection', ['id' => $other_collection->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsReadLater::class),
        ], headers: [
            'Referer' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The read status didn't changed for the other user who owns the link.
        $this->assertFalse($other_user->hasReadLater($link));
        // But the logged user now has to read the link later and owns their own copy.
        $this->assertTrue($user->hasReadLater($link));
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $other_collection->id]);
        $this->assertSame($origin, $new_link->origin);
    }

    public function testLaterRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasReadLater($link));
        $this->assertTrue($news->hasLink($link));
    }

    public function testLaterFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasReadLater($link));
        $this->assertTrue($news->hasLink($link));
    }

    public function testLaterFailsIfNotOwnedAndHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsReadLater::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasReadLater($link));
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]));
    }

    public function testNeverMarksToDismiss(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link));
        $this->assertFalse($news->hasLink($link));
    }

    public function testNeverWorksIfNotOwnedAndNotHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        // The read status didn't changed for the other user who owns the link.
        $this->assertFalse($other_user->hasDismissed($link));
        // But the logged user now dismissed the link and owns their own copy.
        $this->assertTrue($user->hasDismissed($link));
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
    }

    public function testNeverRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsNever::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasDismissed($link));
        $this->assertTrue($news->hasLink($link));
    }

    public function testNeverFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasDismissed($link));
        $this->assertTrue($news->hasLink($link));
    }

    public function testNeverFailsIfNotOwnedAndHidden(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => true,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/read/never", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsNever::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasDismissed($link));
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link->url,
        ]));
    }

    public function testDeleteMarksAsUnread(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsRead($link);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsUnread::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->hasRead($link));
    }

    public function testDeleteDoesNotWorkWithNotOwnedLink(): void
    {
        // This is historical behaviour, but can be changed to work with
        // accessible links now.
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $user->markAsRead($link);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsUnread::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue($user->hasRead($link));
    }

    public function testDeleteRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsRead($link);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf_token' => $this->csrfToken(forms\links\MarkLinkAsUnread::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($user->hasRead($link));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsRead($link);

        $response = $this->appRun('POST', "/links/{$link->id}/read/delete", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue($user->hasRead($link));
    }
}
