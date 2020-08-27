<?php

namespace flusio;

class NewsLinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersNewsLinksCorrectly()
    {
        $user = $this->login();
        $title_news = $this->fake('sentence');
        $title_not_news = $this->fake('sentence');
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_news,
            'is_hidden' => 0,
        ]);
        $this->create('news_link', [
            'user_id' => $user->id,
            'title' => $title_not_news,
            'is_hidden' => 1,
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
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
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
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNotNull($news_link);
    }

    public function testFillSelectsLinksUpToAbout1Hour()
    {
        $news_link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url_1 = $this->fake('url');
        $link_url_2 = $this->fake('url');
        $link_url_3 = $this->fake('url');
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_1,
            'reading_time' => 15,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_2,
            'reading_time' => 25,
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url_3,
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

        $news_link_1 = $news_link_dao->findBy(['url' => $link_url_1]);
        $news_link_2 = $news_link_dao->findBy(['url' => $link_url_2]);
        $news_link_3 = $news_link_dao->findBy(['url' => $link_url_3]);
        $this->assertNotNull($news_link_1);
        $this->assertNotNull($news_link_2);
        $this->assertNotNull($news_link_3);
    }

    public function testFillDoesNotSelectTooLongLinks()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
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

        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testFillDoesNotSelectNotBookmarkedLinks()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'reading_time' => 10,
        ]);
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('post', '/news', [
            'csrf' => $user->csrf,
        ]);

        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testFillRedirectsIfNotConnected()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
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
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testFillFailsIfCsrfIsInvalid()
    {
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
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
        $news_link = $news_link_dao->findBy(['url' => $link_url]);
        $this->assertNull($news_link);
    }

    public function testReadRemovesLinkFromNewsAndBookmarksAndRedirects()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $is_bookmarked = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertTrue($news_link->is_hidden, 'The news should be hidden.');
        $this->assertFalse($is_bookmarked, 'The link should no longer be in bookmarks.');
    }

    public function testReadRedirectsToLoginIfNotConnected()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $is_bookmarked = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($news_link->is_hidden, 'The news should not be hidden.');
        $this->assertTrue($is_bookmarked, 'The link should be in bookmarks.');
    }

    public function testReadFailsIfCsrfIsInvalid()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $is_bookmarked = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($news_link->is_hidden, 'The news should not be hidden.');
        $this->assertTrue($is_bookmarked, 'The link should be in bookmarks.');
    }

    public function testReadFailsIfLinkDoesNotExist()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/-1/read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $is_bookmarked = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($news_link->is_hidden, 'The news should not be hidden.');
        $this->assertTrue($is_bookmarked, 'The link should be in bookmarks.');
    }

    public function testReadFailsIfUserDoesNotOwnTheLink()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $news_link = new models\NewsLink($news_link_dao->find($news_link_id));
        $is_bookmarked = $links_to_collections_dao->exists($link_to_collection_id);
        $this->assertFalse($news_link->is_hidden, 'The news should not be hidden.');
        $this->assertTrue($is_bookmarked, 'The link should be in bookmarks.');
    }

    public function testReadLaterRemovesLinkFromNewsAndAddsToBookmarksAndRedirects()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->assertFalse($exists, 'The news should no longer exist.');
        $this->assertNotNull($db_link_to_collection, 'The link should be in bookmarks.');
    }

    public function testReadLaterCreatesTheLinkIfItDoesNotExistForCurrentUser()
    {
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertNotNull($db_link, 'The link should exist.');
    }

    public function testReadLaterJustRemovesFromNewsIfAlreadyBookmarked()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link_to_collection = $links_to_collections_dao->find($link_to_collection_id);
        $this->assertFalse($exists, 'The news should no longer exist.');
        $this->assertNotNull($db_link_to_collection, 'The link should still be in bookmarks.');
    }

    public function testReadLaterRedirectsToLoginIfNotConnected()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfCsrfIsInvalid()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'A security verification failed.');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfLinkDoesNotExist()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $user->id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', '/news/-1/read-later', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }

    public function testReadLaterFailsIfUserDoesNotOwnTheLink()
    {
        $news_link_dao = new models\dao\NewsLink();
        $link_dao = new models\dao\Link();
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'bookmarks',
        ]);
        $news_link_id = $this->create('news_link', [
            'user_id' => $other_user_id,
            'url' => $link_url,
        ]);

        $response = $this->appRun('post', "/news/{$news_link_id}/read-later", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/news');
        $this->assertFlash('error', 'The link doesn’t exist.');
        $exists = $news_link_dao->exists($news_link_id);
        $db_link = $link_dao->findBy(['url' => $link_url]);
        $this->assertTrue($exists, 'The news should still exist.');
        $this->assertNull($db_link, 'The link should not exist.');
    }
}
