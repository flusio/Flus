<?php

namespace flusio;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowBookmarkedRendersCorrectly()
    {
        $user = $this->login();
        $faker = \Faker\Factory::create();
        $link_title = $faker->words(3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarked',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/bookmarked');

        $this->assertResponse($response, 200, $link_title);
        $this->assertPointer($response, 'collections/show.phtml');
    }

    public function testShowBookmarkedRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/bookmarked');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarked');
    }

    public function testShowBookmarkedFailsIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/bookmarked');

        $this->assertResponse($response, 404, 'It looks like you have no “Bookmarked” collection');
    }
}
