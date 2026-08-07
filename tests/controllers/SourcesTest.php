<?php

namespace App\controllers;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class SourcesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\SqlQueriesHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        models\FeatureFlag::enable('alpha', $user->id);
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'name' => $collection_name,
            'is_public' => true,
        ]);
        /** @var string */
        $feed_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'name' => $feed_name,
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);
        $user->follow($collection->id);
        $user->follow($feed->id);

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'sources/index.html.twig');
        $this->assertResponseContains($response, '2 sources');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $feed_name);
    }

    public function testIndexDoesNotListTheSourcesTheUserCannotView(): void
    {
        $user = $this->login();
        models\FeatureFlag::enable('alpha', $user->id);
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $collection_name,
            'is_public' => false,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testIndexDoesNotListTheCollectionsThatAreNotFollowed(): void
    {
        $user = $this->login();
        models\FeatureFlag::enable('alpha', $user->id);
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        CollectionFactory::create([
            'type' => 'collection',
            'name' => $collection_name,
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testIndexExecutesAConstantNumberOfQueries(): void
    {
        $user = $this->login();
        models\FeatureFlag::enable('alpha', $user->id);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $other_user = UserFactory::create();

        foreach (range(1, 10) as $index_source) {
            /** @var string */
            $feed_url = $this->fake('url');
            $feed = CollectionFactory::create([
                'type' => 'feed',
                'is_public' => true,
                'feed_url' => $feed_url,
            ]);
            $user->follow($feed->id);
            $stream->addSource($feed);

            // The collections are listed as well: contrary to the feeds, they
            // display their publishers.
            $collection = CollectionFactory::create([
                'user_id' => $other_user->id,
                'type' => 'collection',
                'is_public' => true,
            ]);
            $user->follow($collection->id);
            $stream->addSource($collection);
        }

        list($response, $count_queries) = $this->countSqlQueries(function (): \Minz\Response {
            $response = $this->appRun('GET', '/sources');

            // The templates are rendered lazily, so the response must be
            // rendered here for the queries of the views to be counted.
            $this->assertInstanceOf(\Minz\Response::class, $response);
            $response->render();

            return $response;
        });

        $this->assertResponseCode($response, 200);
        // The number of queries must not grow with the number of sources: the
        // publishers, the number of streams and the time filters are all
        // loaded in batch by models\collections\Preloader.
        $this->assertLessThanOrEqual(20, $count_queries);
    }

    public function testIndexRedirectsIfTheUserIsNotAlpha(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 302, '/feeds');
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $redirect_to = urlencode('/sources');

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }
}
