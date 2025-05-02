<?php

namespace App\controllers\api\v1\journal;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateMarksTheLinksOfTheJournalAsRead(): void
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

        $this->assertFalse($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));

        $response = $this->apiRun('POST', '/api/v1/journal/read');

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link1->isReadBy($user));
        $this->assertTrue($link2->isReadBy($user));
    }

    public function testCreateCanMarkAsReadByDate(): void
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

        $this->assertFalse($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));

        $response = $this->apiRun('POST', '/api/v1/journal/read', [
            'date' => '2025-08-22',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));
    }

    public function testCreateCanMarkAsReadBySource(): void
    {
        $user = $this->login();
        $news = $user->news();
        $collection = CollectionFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'source_type' => 'collection',
            'source_resource_id' => $collection->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news);
        $link2->addCollection($news);

        $this->assertFalse($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));

        $response = $this->apiRun('POST', '/api/v1/journal/read', [
            'source' => "collection#{$collection->id}",
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));
    }

    public function testCreateFailsIfNotConnected(): void
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

        $this->assertFalse($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));

        $response = $this->apiRun('POST', '/api/v1/journal/read');

        $this->assertResponseCode($response, 401);
        $this->assertFalse($link1->isReadBy($user));
        $this->assertFalse($link2->isReadBy($user));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
