<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\GroupFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testIndexReturnsCollectionsOfUser(): void
    {
        $user = $this->login();
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => 'My group',
        ]);
        $collection1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My favourites',
            'description' => 'My favourite links',
            'is_public' => true,
        ]);
        $collection2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My shares',
            'description' => 'My shared links',
            'group_id' => $group->id,
            'is_public' => false,
        ]);

        $response = $this->apiRun('GET', '/api/v1/collections');

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, [
            [
                'id' => $collection1->id,
                'name' => 'My favourites',
                'description' => 'My favourite links',
                'group' => null,
                'is_public' => true,
            ],
            [
                'id' => $collection2->id,
                'name' => 'My shares',
                'description' => 'My shared links',
                'group' => 'My group',
                'is_public' => false,
            ],
        ]);
    }

    public function testIndexFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My favourites',
            'description' => 'My favourite links',
            'is_public' => true,
        ]);
        $collection2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => false,
        ]);

        $response = $this->apiRun('GET', '/api/v1/collections');

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
