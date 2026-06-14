<?php

namespace App\controllers\api\v1\links;

use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateMarksTheLinkAsRead(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($user->hasRead($link));

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/read");

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasRead($link));
    }

    public function testCreateFailsIfTheLinkIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $this->assertFalse($user->hasRead($link));

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/read");

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasRead($link));
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testCreateFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->apiRun('POST', '/api/v1/links/not-an-id/read');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($user->hasRead($link));

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/read");

        $this->assertResponseCode($response, 401);
        $this->assertFalse($user->hasRead($link));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteUnmarksTheLinkAsRead(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsRead($link);

        $this->assertTrue($user->hasRead($link));

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/read");

        $this->assertResponseCode($response, 200);
        $this->assertFalse($user->hasRead($link));
    }

    public function testDeleteFailsIfTheLinkIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/read");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testDeleteFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->apiRun('DELETE', '/api/v1/links/not-an-id/read');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testDeleteFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $user->markAsRead($link);

        $this->assertTrue($user->hasRead($link));

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/read");

        $this->assertResponseCode($response, 401);
        $this->assertTrue($user->hasRead($link));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
