<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use tests\factories\UserFactory;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateCreatesASessionAndReturnsAToken(): void
    {
        $email = 'alix@example.com';
        $password = 'secret';
        $app_name = 'My app';
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => models\User::passwordHash($password),
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $response = $this->apiRun('POST', '/api/v1/sessions', [
            'email' => $email,
            'password' => $password,
            'app_name' => $app_name,
        ]);

        $this->assertResponseCode($response, 200);
        $current_user = auth\CurrentUser::get();
        $session = auth\CurrentUser::session();
        $this->assertNotNull($current_user);
        $this->assertNotNull($session);
        $this->assertSame($user->id, $current_user->id);
        $this->assertSame($app_name, $session->name);
        $this->assertApiResponse($response, [
            'token' => $session->token,
        ]);
    }

    public function testCreateFailsIfAppNameIsMissing(): void
    {
        $email = 'alix@example.com';
        $password = 'secret';
        $app_name = '';
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => models\User::passwordHash($password),
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $response = $this->apiRun('POST', '/api/v1/sessions', [
            'email' => $email,
            'password' => $password,
            'app_name' => $app_name,
        ]);

        $this->assertResponseCode($response, 400);
        $current_user = auth\CurrentUser::get();
        $session = auth\CurrentUser::session();
        $this->assertNull($current_user);
        $this->assertNull($session);
        $this->assertApiError(
            $response,
            'app_name',
            ['presence', 'The app name is required.']
        );
    }

    public function testCreateFailsIfEmailIsInvalid(): void
    {
        $email = 'alix@example.com';
        $password = 'secret';
        $app_name = 'My app';
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => models\User::passwordHash($password),
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $response = $this->apiRun('POST', '/api/v1/sessions', [
            'email' => 'not-the-email@example.com',
            'password' => $password,
            'app_name' => $app_name,
        ]);

        $this->assertResponseCode($response, 400);
        $current_user = auth\CurrentUser::get();
        $session = auth\CurrentUser::session();
        $this->assertNull($current_user);
        $this->assertNull($session);
        $this->assertApiError(
            $response,
            '@base',
            ['invalid_credentials', 'The credentials are invalid.']
        );
    }

    public function testCreateFailsIfPasswordIsInvalid(): void
    {
        $email = 'alix@example.com';
        $password = 'secret';
        $app_name = 'My app';
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => models\User::passwordHash($password),
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $response = $this->apiRun('POST', '/api/v1/sessions', [
            'email' => $email,
            'password' => 'not the password',
            'app_name' => $app_name,
        ]);

        $this->assertResponseCode($response, 400);
        $current_user = auth\CurrentUser::get();
        $session = auth\CurrentUser::session();
        $this->assertNull($current_user);
        $this->assertNull($session);
        $this->assertApiError(
            $response,
            '@base',
            ['invalid_credentials', 'The credentials are invalid.']
        );
    }
}
