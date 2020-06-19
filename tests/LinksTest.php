<?php

namespace flusio;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $faker = \Faker\Factory::create();
        $title = $faker->words(3, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $title,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponse($response, 200, $title);
        $this->assertPointer($response, 'links/show.phtml');
    }

    public function testShowFailsIfNotConnected()
    {
        $faker = \Faker\Factory::create();
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $faker->words(3, true),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponse($response, 401, 'You must be connected to see this page');
    }

    public function testShowFailsIfTheLinkDoesNotExist()
    {
        $faker = \Faker\Factory::create();
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id');

        $this->assertResponse($response, 404, 'This link doesn’t exist.');
    }

    public function testShowFailsIfUserDoesNotOwnTheLink()
    {
        $faker = \Faker\Factory::create();
        $current_user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'title' => $faker->words(3, true),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}");

        $this->assertResponse($response, 404, 'This link doesn’t exist.');
    }

    public function testAddCreatesLinkAndRedirects()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $url = $faker->url;

        $this->assertSame(0, $link_dao->count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->listAll()[0]);

        $this->assertResponse($response, 302, "/links/{$link->id}");
        $this->assertSame($url, $link->url);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection_id, $link->collectionIds());
    }

    public function testAddDoesNotCreateLinkIfItExists()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $url = $faker->url;
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->find($link_id));
        $this->assertContains($collection_id, $link->collectionIds());
    }

    public function testAddCreatesLinkIfItExistsForAnotherUser()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $another_user_id = $this->create('user');
        $url = $faker->url;
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $this->create('link', [
            'user_id' => $another_user_id,
            'url' => $url,
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(0, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertSame(2, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->findBy(['user_id' => $user->id]));
        $this->assertContains($collection_id, $link->collectionIds());
    }

    public function testAddHandlesMultipleCollections()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $url = $faker->url;
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id_1,
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(1, $links_to_collections_dao->count());

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $url,
            'collection_ids' => [$collection_id_1, $collection_id_2],
        ]);

        $this->assertSame(1, $link_dao->count());
        $this->assertSame(2, $links_to_collections_dao->count());

        $link = new models\Link($link_dao->find($link_id));
        $this->assertContains($collection_id_1, $link->collectionIds());
        $this->assertContains($collection_id_2, $link->collectionIds());
    }

    public function testAddFailsIfNotConnected()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();

        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $faker->url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarked');
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfCsrfIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/links', [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $faker->url,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfUrlIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => 'ftp://' . $faker->domainName,
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertFlash('errors', ['url' => 'Link scheme must be either http or https.']);
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfUrlIsMissing()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_ids' => [$collection_id],
        ]);

        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertFlash('errors', ['url' => 'The link is required.']);
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfCollectionDoesNotExist()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();

        $user = $this->login();

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $faker->url,
            'collection_ids' => ['does not exist'],
        ]);

        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertFlash('error', 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testAddFailsIfCollectionIsMissing()
    {
        $faker = \Faker\Factory::create();
        $link_dao = new models\dao\Link();

        $user = $this->login();

        $response = $this->appRun('post', '/links', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'url' => $faker->url,
            'collection_ids' => [],
        ]);

        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertFlash('error', 'The link must be associated to a collection.');
        $this->assertSame(0, $link_dao->count());
    }

    public function testFetchUpdatesLinkWithTheTitleAndRedirects()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $db_link = $link_dao->find($link_id);
        $expected_title = 'GitHub - flusio/flusio: The citizen social media';
        $this->assertSame($expected_title, $db_link['title']);
        $this->assertSame(200, $db_link['fetched_code']);
    }

    public function testFetchDoesNotChangeTitleIfUnreachable()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://flus.fr/does_not_exist.html',
            'title' => 'https://flus.fr/does_not_exist.html',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://flus.fr/does_not_exist.html';
        $this->assertSame($expected_title, $db_link['title']);
        $this->assertSame(404, $db_link['fetched_code']);
    }

    public function testFetchFailsIfCsrfIsInvalid()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $db_link['title']);
    }

    public function testFetchFailsIfNotConnected()
    {
        $link_dao = new models\dao\Link();

        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 401, 'You must be connected to see this page');
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $db_link['title']);
    }

    public function testFetchFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('post', "/links/do-not-exist/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 404, 'This link doesn’t exist');
    }

    public function testFetchFailsIfUserDoesNotOwnTheLink()
    {
        $link_dao = new models\dao\Link();

        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 404, 'This link doesn’t exist');
        $db_link = $link_dao->find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $db_link['title']);
    }

    public function testRemoveCollectionRemovesLinkFromCollection()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);


        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertFalse($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionRedirectsToLoginIfNotConnected()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);


        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarked');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionFailsIfCsrfIsInvalid()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);


        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionFailsIfLinkDoesNotExist()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/not-an-id/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);

        $this->assertResponse($response, 404, 'This link-collection relation doesn’t exist.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionFailsIfCollectionDoesNotExist()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => 'not an id',
        ]);

        $this->assertResponse($response, 404, 'This link-collection relation doesn’t exist.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionFailsIfUserDoesNotOwnTheLink()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);

        $this->assertResponse($response, 404, 'This link-collection relation doesn’t exist.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionFailsIfUserDoesNotOwnTheCollection()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);
        $link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);

        $this->assertResponse($response, 404, 'This link-collection relation doesn’t exist.');
        $this->assertTrue($links_to_collections_dao->exists($link_to_collection_id));
    }

    public function testRemoveCollectionFailsIfThereIsNoRelationBetweenLinkAndCollection()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/remove_collection", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => \Minz\Url::for('show bookmarked'),
            'collection_id' => $collection_id,
        ]);

        $this->assertResponse($response, 404, 'This link-collection relation doesn’t exist.');
    }
}
