<?php

namespace App\controllers\api\v1\links;

use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LaterTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateMarksTheLinkToReadLater(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($link->isInBookmarksOf($user));

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/later");

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link->isInBookmarksOf($user));
    }

    public function testCreateFailsIfTheLinkIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $this->assertFalse($link->isInBookmarksOf($user));

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/later");

        $this->assertResponseCode($response, 403);
        $this->assertFalse($link->isInBookmarksOf($user));
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testCreateFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->apiRun('POST', '/api/v1/links/not-an-id/later');

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

        $this->assertFalse($link->isInBookmarksOf($user));

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/later");

        $this->assertResponseCode($response, 401);
        $this->assertFalse($link->isInBookmarksOf($user));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
