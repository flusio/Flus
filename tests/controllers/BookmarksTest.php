<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class BookmarksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('GET', '/bookmarks');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'bookmarks/index.phtml');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $response = $this->appRun('GET', '/bookmarks');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
    }

    public function testIndexRedirectsIfPageIsOutOfBound()
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);

        $response = $this->appRun('GET', '/bookmarks', [
            'page' => 0,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks?page=1');
    }
}
