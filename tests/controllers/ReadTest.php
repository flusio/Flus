<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('GET', '/read');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'read/index.phtml');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user = UserFactory::create();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('GET', '/read');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fread');
    }

    public function testIndexRedirectsIfPageOutOfBound()
    {
        $user = $this->login();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('GET', '/read', [
            'page' => 2,
        ]);

        $this->assertResponseCode($response, 302, '/read?page=1');
    }
}
