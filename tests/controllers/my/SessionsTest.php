<?php

namespace flusio\controllers\my;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;

    public function testIndexRendersCorrectly(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at,
        ]);

        $response = $this->appRun('GET', '/my/sessions');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'List and manage your login sessions.');
        $this->assertResponsePointer($response, 'my/sessions/index.phtml');
    }

    public function testIndexRedirectsIfPasswordIsNotConfirmed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 16, 9000);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at,
        ]);

        $response = $this->appRun('GET', '/my/sessions');

        $this->assertResponseCode($response, 302, '/my/security/confirmation?from=%2Fmy%2Fsessions');
    }

    public function testIndexRedirectsIfUserIsNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/sessions');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsessions');
    }
}
