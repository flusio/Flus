<?php

namespace App\controllers\streams;

use App\forms;
use App\models;
use App\utils;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class SharesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $stream_name = $this->fake('text', 50);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/share");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $this->assertResponseContains($response, $stream_name);
    }

    public function testIndexRendersExistingShares(): void
    {
        $user = $this->login();
        /** @var string */
        $username = $this->fake('username');
        $other_user = UserFactory::create([
            'username' => $username,
        ]);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('GET', "/streams/{$stream->id}/share");

        $this->assertResponseContains($response, $username);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/share");

        $redirect_to = urlencode("/streams/{$stream->id}/share");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testIndexFailsIfStreamDoesNotExist(): void
    {
        $user = $this->login();
        StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', '/streams/not-an-id/share');

        $this->assertResponseCode($response, 404);
    }

    public function testIndexFailsIfUserDoesNotOwnTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/share");

        $this->assertResponseCode($response, 403);
    }

    public function testIndexFailsIfStreamIsSharedWithUser(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $stream->shareWith($user);

        $response = $this->appRun('GET', "/streams/{$stream->id}/share");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateCreatesStreamShare(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $stream_share = models\StreamShare::findBy([
            'stream_id' => $stream->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNotNull($stream_share);
    }

    public function testCreateAcceptsProfileUrlAsUserId(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => \Minz\Url::absoluteFor('profile', ['id' => $other_user->id]),
        ]);

        $stream_share = models\StreamShare::findBy([
            'stream_id' => $stream->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNotNull($stream_share);
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $other_user->id,
        ]);

        $redirect_to = urlencode("/streams/{$stream->id}/share");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testCreateFailsIfStreamDoesNotExist(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', '/streams/not-an-id/share', [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => 'not the token',
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testCreateFailsIfUserIdIsTheCurrentUserId(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $user->id,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $this->assertResponseContains($response, 'You can’t share access with the owner of the stream.');
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testCreateFailsIfUserIdDoesNotExist(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => 'not a user id',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $this->assertResponseContains($response, 'This user doesn’t exist');
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testCreateFailsIfStreamIsAlreadySharedWithUserId(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $this->assertResponseContains($response, 'The stream is already shared with this user.');
        $this->assertSame(1, models\StreamShare::count());
    }

    public function testCreateFailsIfUserDoesNotOwnTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $yet_another_user->id,
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testCreateFailsIfStreamIsSharedWithUser(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $stream->shareWith($user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/share", [
            'csrf_token' => $this->csrfToken(forms\streams\ShareStream::class),
            'user_id' => $yet_another_user->id,
        ]);

        $this->assertResponseCode($response, 403);
        $stream_share = models\StreamShare::findBy([
            'stream_id' => $stream->id,
            'user_id' => $yet_another_user->id,
        ]);
        $this->assertNull($stream_share);
    }

    public function testDeleteDeletesStreamShare(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/unshare", [
            'csrf_token' => $this->csrfToken(forms\streams\UnshareStream::class),
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $this->assertSame(0, models\StreamShare::count());
    }

    public function testDeleteRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/unshare", [
            'csrf_token' => $this->csrfToken(forms\streams\UnshareStream::class),
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame(1, models\StreamShare::count());
    }

    public function testDeleteFailsIfStreamDoesNotExist(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('POST', '/streams/not-an-id/unshare', [
            'csrf_token' => $this->csrfToken(forms\streams\UnshareStream::class),
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(1, models\StreamShare::count());
    }

    public function testDeleteFailsIfUserDoesNotOwnTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $stream->shareWith($yet_another_user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/unshare", [
            'csrf_token' => $this->csrfToken(forms\streams\UnshareStream::class),
            'user_id' => $yet_another_user->id,
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(1, models\StreamShare::count());
    }

    public function testDeleteFailsIfUserIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/unshare", [
            'csrf_token' => $this->csrfToken(forms\streams\UnshareStream::class),
            'user_id' => 'not an id',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $error = utils\Notification::popError();
        $this->assertSame('This user doesn’t exist.', $error);
        $this->assertSame(1, models\StreamShare::count());
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->shareWith($other_user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/unshare", [
            'csrf_token' => 'not the token',
            'user_id' => $other_user->id,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'streams/shares/index.html.twig');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertSame(1, models\StreamShare::count());
    }
}
