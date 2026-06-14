<?php

namespace App\controllers\api\v1\journal;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testDeleteAllRemovesTheLinksFromTheJournal(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $this->assertFalse($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));

        $response = $this->apiRun('DELETE', '/api/v1/journal/links');

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasDismissed($link1));
        $this->assertTrue($user->hasDismissed($link2));
    }

    public function testDeleteAllCanRemoveLinksByDate(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news, at: new \DateTimeImmutable('2025-08-22'));
        $link2->addCollection($news, at: new \DateTimeImmutable('2025-08-23'));

        $this->assertFalse($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));

        $response = $this->apiRun('DELETE', '/api/v1/journal/links', [
            'date' => '2025-08-22'
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));
    }

    public function testDeleteAllCanRemoveLinksByOrigin(): void
    {
        $user = $this->login();
        $news = $user->news();
        $collection = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_id' => $collection->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $this->assertFalse($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));

        $response = $this->apiRun('DELETE', '/api/v1/journal/links', [
            'source' => $collection->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));
    }

    public function testDeleteAllFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $this->assertFalse($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));

        $response = $this->apiRun('DELETE', '/api/v1/journal/links');

        $this->assertResponseCode($response, 401);
        $this->assertFalse($user->hasDismissed($link1));
        $this->assertFalse($user->hasDismissed($link2));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteRemovesTheLinkFromTheJournal(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($user->hasDismissed($link));

        $response = $this->apiRun('DELETE', "/api/v1/journal/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasDismissed($link));
    }

    public function testDeleteFailsIfTheLinkIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $this->assertFalse($user->hasDismissed($link));

        $response = $this->apiRun('DELETE', "/api/v1/journal/links/{$link->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
        $this->assertFalse($user->hasDismissed($link));
    }

    public function testDeleteFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->apiRun('DELETE', '/api/v1/journal/links/not-an-id');

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

        $this->assertFalse($user->hasDismissed($link));

        $response = $this->apiRun('DELETE', "/api/v1/journal/links/{$link->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
        $this->assertFalse($user->hasDismissed($link));
    }
}
