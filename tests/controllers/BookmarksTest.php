<?php

namespace App\controllers;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class BookmarksTest extends \PHPUnit\Framework\TestCase
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
        $this->assertResponseTemplateName($response, 'bookmarks/index.html.twig');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/bookmarks');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
    }
}
