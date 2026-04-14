<?php

namespace App\controllers;

use App\models;
use tests\factories\UserFactory;

class ProfilesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

    public function testShowRedirectsToProfileLinks(): void
    {
        /** @var string */
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseCode($response, 302, "/p/{$user->id}/links");
    }

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserIsSupportUser(): void
    {
        $support_user = models\User::supportUser();

        $response = $this->appRun('GET', "/p/{$support_user->id}");

        $this->assertResponseCode($response, 404);
    }
}
