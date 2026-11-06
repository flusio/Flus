<?php

namespace App\controllers;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FeedFactory;
use tests\factories\ImportationFactory;
use tests\factories\LinkFactory;
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
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'is_public' => true,
        ]);
        /** @var string */
        $feed_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $feed = FeedFactory::create([
            'name' => $feed_name,
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);
        $user->follow($collection);
        $user->follow($feed);

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'sources/index.html.twig');
        $this->assertResponseContains($response, '2 sources');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $feed_name);
    }

    public function testIndexDisplaysTheNamesGivenByTheUser(): void
    {
        $user = $this->login();
        /** @var string */
        $feed_url = $this->fake('url');
        $feed = FeedFactory::create([
            'name' => 'Carnet de Flus',
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);
        $follow = $user->follow($feed);
        $follow->name = 'Flus news';
        $follow->save();

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Flus news');
        $this->assertResponseNotContains($response, 'Carnet de Flus');
    }

    public function testIndexDoesNotListTheSourcesTheUserCannotView(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'name' => $collection_name,
            'is_public' => false,
        ]);
        $user->follow($collection);

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testIndexDoesNotListTheSourcesThatAreNotFollowed(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        CollectionFactory::create([
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
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $other_user = UserFactory::create();

        foreach (range(1, 10) as $index_source) {
            /** @var string */
            $feed_url = $this->fake('url');
            $feed = FeedFactory::create([
                'is_public' => true,
                'feed_url' => $feed_url,
            ]);
            $user->follow($feed);
            $stream->addSource($feed);

            // The collections are listed as well: contrary to the feeds, they
            // display their publishers.
            $collection = CollectionFactory::create([
                'user_id' => $other_user->id,
                'is_public' => true,
            ]);
            $user->follow($collection);
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
        // loaded in batch by models\link_lists\Preloader.
        $this->assertLessThanOrEqual(20, $count_queries);
    }

    public function testIndexRendersTheOngoingImportation(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’re importing your OPML file');
        $this->assertResponseContains($response, "/importations/{$importation->id}");
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $redirect_to = urlencode('/sources');

        $response = $this->appRun('GET', '/sources');

        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'description' => '**foo bar**',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseContains($response, '<strong>foo bar</strong>');
        $this->assertResponseTemplateName($response, 'sources/show.html.twig');
    }

    public function testShowDisplaysTheNameGivenByTheUser(): void
    {
        $user = $this->login();
        /** @var string */
        $feed_url = $this->fake('url');
        $feed = FeedFactory::create([
            'name' => 'Carnet de Flus',
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);
        $follow = $user->follow($feed);
        $follow->name = 'Flus news';
        $follow->save();

        $response = $this->appRun('GET', "/collections/{$feed->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '<h1 class="text--break">Flus news</h1>');
        $this->assertResponseContains($response, 'Original name: Carnet de Flus');
    }

    public function testShowRendersCorrectlyIfPublicAndNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseTemplateName($response, 'sources/show.html.twig');
    }

    public function testShowRendersCorrectlyIfPublicAndDoesNotOwnTheLink(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $owner->id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseTemplateName($response, 'sources/show.html.twig');
    }

    public function testShowRendersCorrectlyIfSourceIsPrivateAndSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'title' => $link_title,
        ]);
        $collection->addLinks([$link]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseTemplateName($response, 'sources/show.html.twig');
    }

    public function testShowRendersCorrectlyIfSourceIsPrivateAndSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'title' => $link_title,
        ]);
        $collection->addLinks([$link]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseTemplateName($response, 'sources/show.html.twig');
    }

    public function testShowHidesHiddenLinksInPublicSources(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
            'is_hidden' => true,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowRedirectsIfPrivateAndNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}");
    }

    public function testShowFailsIfPageIsOutOfBound(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/collections/{$collection->id}", [
            'page' => 0,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfSourceDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/collections/unknown');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfPrivateAndNoSharedAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
    }
}
