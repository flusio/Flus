<?php

namespace App\controllers;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
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
        $this->assertResponseTemplateName($response, 'read/index.phtml');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected(): void
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
}
