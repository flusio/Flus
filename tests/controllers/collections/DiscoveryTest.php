<?php

namespace flusio\controllers\collections;

use flusio\models;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $this->assertResponse($response, 200, $collection_name);
        $this->assertPointer($response, 'collections/discovery/show.phtml');
    }

    public function testShowDoesNotListOwnedCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testShowDoesNotListEmptyCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testShowDoesNotListPrivateCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 0,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testShowDoesNotListFeedCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testShowDoesNotCountHiddenLinks()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id1 = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $link_id2 = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id2,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringContainsString('1 link', $output);
        $this->assertStringNotContainsString('2 links', $output);
    }

    public function testShowRedirectsIfPageIsOutOfBound()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover', [
            'page' => 0,
        ]);

        $this->assertResponse($response, 302, '/collections/discover?page=1');
    }

    public function testShowRedirectsIfNotConnected()
    {
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2Fdiscover");
    }
}
