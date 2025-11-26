<?php

namespace App\controllers\my;

use App\auth;
use App\forms;
use App\models;
use tests\factories\SessionFactory;
use tests\factories\UserFactory;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login(confirmed_password_at: $confirmed_at);

        $response = $this->appRun('GET', '/my/sessions');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'List and manage your login sessions.');
        $this->assertResponseTemplateName($response, 'my/sessions/index.html.twig');
    }

    public function testIndexRedirectsIfPasswordIsNotConfirmed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 16, 9000);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login(confirmed_password_at: $confirmed_at);

        $response = $this->appRun('GET', '/my/sessions');

        $this->assertResponseCode($response, 302, '/my/security/confirmation?redirect_to=%2Fmy%2Fsessions');
    }

    public function testIndexRedirectsIfUserIsNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/sessions');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsessions');
    }

    public function testDeleteRemovesTheSessionAndRedirects(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $user = $this->login(confirmed_password_at: $confirmed_at);
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/my/sessions/{$session->id}/deletion", [
            'csrf_token' => $this->csrfToken(forms\security\DeleteSession::class),
        ]);

        $this->assertResponseCode($response, 302, '/my/sessions');
        $this->assertFalse(models\Session::exists($session->id));
    }

    public function testDeleteLogsOutIfGivenSessionIsCurrentSession(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $user = $this->login(confirmed_password_at: $confirmed_at);
        $session = auth\CurrentUser::session();

        $response = $this->appRun('POST', "/my/sessions/{$session->id}/deletion", [
            'csrf_token' => $this->csrfToken(forms\security\DeleteSession::class),
        ]);

        $this->assertResponseCode($response, 302, '/my/sessions');
        $this->assertFalse(models\Session::exists($session->id));
        $this->assertNull(auth\CurrentUser::get());
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $cookie = $response->cookies()['session_token'];
        $this->assertSame('', $cookie['value']);
        $this->assertTrue($cookie['options']['expires'] < \Minz\Time::now()->getTimestamp());
    }

    public function testDeleteDoesNotRemoveSessionIfCsrfIsInvalid(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $user = $this->login(confirmed_password_at: $confirmed_at);
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/my/sessions/{$session->id}/deletion", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/my/sessions');
        $this->assertTrue(models\Session::exists($session->id));
    }

    public function testDeleteRedirectsIfUserIsNotConnected(): void
    {
        $user = UserFactory::create();
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/my/sessions/{$session->id}/deletion", [
            'csrf_token' => $this->csrfToken(forms\security\DeleteSession::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Session::exists($session->id));
    }

    public function testDeleteRedirectsIfPasswordIsNotConfirmed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 16, 9000);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $user = $this->login(confirmed_password_at: $confirmed_at);
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/my/sessions/{$session->id}/deletion", [
            'csrf_token' => $this->csrfToken(forms\security\DeleteSession::class),
        ]);

        $this->assertResponseCode($response, 302, '/my/security/confirmation?redirect_to=%2F');
        $this->assertTrue(models\Session::exists($session->id));
    }

    public function testDeleteFailsIfSessionDoesNotExist(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $user = $this->login(confirmed_password_at: $confirmed_at);
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/my/sessions/not-an-id/deletion", [
            'csrf_token' => $this->csrfToken(forms\security\DeleteSession::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Session::exists($session->id));
    }
}
