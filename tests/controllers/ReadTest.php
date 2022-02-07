<?php

namespace flusio\controllers;

use flusio\models;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('get', '/read');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'read/index.phtml');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('get', '/read');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fread');
    }

    public function testIndexRedirectsIfPageOutOfBound()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('get', '/read', [
            'page' => 2,
        ]);

        $this->assertResponseCode($response, 302, '/read?page=1');
    }
}
