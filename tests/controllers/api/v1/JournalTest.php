<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class JournalTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\ApiHelper;

    public function testIndexReturnsTheLinksOfTheJournal(): void
    {
        $this->freeze();
        $user = $this->login();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($news, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', '/api/v1/journal');

        $this->assertResponseCode($response, 200);
        $link1->published_at = \Minz\Time::ago(5, 'minutes');
        $link2->published_at = \Minz\Time::ago(10, 'minutes');
        $this->assertApiResponse($response, [
            $link1->toJson($user),
            $link2->toJson($user),
        ]);
    }

    public function testIndexFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($news, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($news, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', '/api/v1/journal');

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testCreateAddsLinksToTheJournal(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link1->addCollection($collection);
        $link2->addCollection($collection);
        $user->follow($collection->id);

        $response = $this->apiRun('POST', '/api/v1/journal');

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, [
            'count' => 2,
        ]);
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $news = $user->news();
        $link1 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link1->addCollection($collection);
        $link2->addCollection($collection);
        $user->follow($collection->id);

        $response = $this->apiRun('POST', '/api/v1/journal');

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
