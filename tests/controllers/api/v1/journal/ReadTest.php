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

        $this->assertFalse($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));

        $response = $this->apiRun('POST', '/api/v1/journal/read');

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasRead($link1));
        $this->assertTrue($user->hasRead($link2));
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

        $this->assertFalse($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));

        $response = $this->apiRun('POST', '/api/v1/journal/read', [
            'date' => '2025-08-22',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));
    }

    public function testCreateCanMarkAsReadByOrigin(): void
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

        $this->assertFalse($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));

        $response = $this->apiRun('POST', '/api/v1/journal/read', [
            'source' => $collection->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));
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

        $this->assertFalse($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));

        $response = $this->apiRun('POST', '/api/v1/journal/read');

        $this->assertResponseCode($response, 401);
        $this->assertFalse($user->hasRead($link1));
        $this->assertFalse($user->hasRead($link2));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
