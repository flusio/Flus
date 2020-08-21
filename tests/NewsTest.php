<?php

namespace flusio;

class NewsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersNewsLinksCorrectly()
    {
        $user = $this->login();
        $title_news = $this->fake('sentence');
        $title_not_news = $this->fake('sentence');
        $this->create('link', [
            'user_id' => $user->id,
            'title' => $title_news,
            'in_news' => 1,
        ]);
        $this->create('link', [
            'user_id' => $user->id,
            'title' => $title_not_news,
            'in_news' => 0,
        ]);

        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString($title_news, $response_output);
        $this->assertStringNotContainsString($title_not_news, $response_output);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/news');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
    }

    public function testFillSelectsLinksForNewsAndRedirects()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $link = new models\Link($link_dao->find($link_id));
        $this->assertTrue($link->in_news);
    }

    public function testFillSelectsLinksUpToAbout1Hour()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 15,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 25,
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 20,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_1,
            'collection_id' => $bookmarks_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_2,
            'collection_id' => $bookmarks_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id_3,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $link_1 = new models\Link($link_dao->find($link_id_1));
        $link_2 = new models\Link($link_dao->find($link_id_2));
        $link_3 = new models\Link($link_dao->find($link_id_3));
        $this->assertTrue($link_1->in_news, 'Link 1 should be selected');
        $this->assertTrue($link_2->in_news, 'Link 2 should be selected');
        $this->assertTrue($link_3->in_news, 'Link 3 should be selected');
    }

    public function testFillDoesNotSelectTooLongLinks()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 75,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $link = new models\Link($link_dao->find($link_id));
        $this->assertFalse($link->in_news);
    }

    public function testFillDoesNotSelectNotBookmarkedLinks()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $link = new models\Link($link_dao->find($link_id));
        $this->assertFalse($link->in_news);
    }

    public function testFillRedirectsIfNotConnected()
    {
        $link_dao = new models\dao\Link();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'in_news' => 0,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $link = new models\Link($link_dao->find($link_id));
        $this->assertFalse($link->in_news);
    }

    public function testFillFailsIfCsrfIsInvalid()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'in_news' => 0,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks_id,
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $link = new models\Link($link_dao->find($link_id));
        $this->assertFalse($link->in_news);
    }
}
