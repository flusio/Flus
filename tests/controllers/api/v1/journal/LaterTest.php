<?php

namespace App\controllers\api\v1\journal;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LaterTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateMarksTheLinksOfTheJournalAsReadLater(): void
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

        $this->assertFalse($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));

        $response = $this->apiRun('POST', '/api/v1/journal/later');

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link1->isInBookmarksOf($user));
        $this->assertTrue($link2->isInBookmarksOf($user));
    }

    public function testCreateCanMakAsReadLaterByDate(): void
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

        $this->assertFalse($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));

        $response = $this->apiRun('POST', '/api/v1/journal/later', [
            'date' => '2025-08-22'
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));
    }

    public function testCreateCanMakAsReadLaterBySource(): void
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

        $this->assertFalse($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));

        $response = $this->apiRun('POST', '/api/v1/journal/later', [
            'source' => "collection#{$collection->id}",
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertTrue($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));
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

        $this->assertFalse($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));

        $response = $this->apiRun('POST', '/api/v1/journal/read');

        $this->assertResponseCode($response, 401);
        $this->assertFalse($link1->isInBookmarksOf($user));
        $this->assertFalse($link2->isInBookmarksOf($user));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
