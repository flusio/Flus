<?php

namespace App\controllers\streams;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class SourcesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\HttpHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);
        /** @var string */
        $source_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $source = CollectionFactory::create([
            'type' => 'feed',
            'name' => $source_name,
            'feed_site_url' => $feed_url,
            'is_public' => true,
        ]);
        $stream->addSource($source);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "Sources of {$stream_name}");
        $this->assertResponseContains($response, $source_name);
        $this->assertResponseTemplateName($response, 'streams/sources/index.html.twig');
    }

    public function testIndexDoesNotListTheSourcesTheUserCannotView(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        /** @var string */
        $public_source_name = $this->fake('words', 3, true);
        $public_source = CollectionFactory::create([
            'type' => 'collection',
            'name' => $public_source_name,
            'is_public' => true,
            'publication_frequency_per_year' => 52,
        ]);
        /** @var string */
        $private_source_name = $this->fake('words', 3, true);
        $private_source = CollectionFactory::create([
            'type' => 'collection',
            'name' => $private_source_name,
            'is_public' => false,
            'user_id' => $other_user->id,
            'publication_frequency_per_year' => 7 * 365,
        ]);
        $stream->addSource($public_source);
        $stream->addSource($private_source);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $public_source_name);
        $this->assertResponseNotContains($response, $private_source_name);
        $this->assertResponseContains($response, '1 source');
        // The publication frequency only sums the sources that are listed: the
        // rate of the private source cannot be read anywhere. The value is
        // chosen so it doesn't collide with the labels of the frequency filter.
        $this->assertResponseContains($response, '1 link per week');
        $this->assertResponseNotContains($response, '7 links per day');
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fstreams%2F{$stream->id}%2Fsources");
    }

    public function testIndexFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/streams/unknown/sources');

        $this->assertResponseCode($response, 404);
    }

    public function testIndexFailsIfTheUserCannotViewTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources");

        $this->assertResponseCode($response, 403);
    }

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);
        /** @var string */
        $source_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $source = CollectionFactory::create([
            'type' => 'feed',
            'name' => $source_name,
            'feed_site_url' => $feed_url,
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "Sources of {$stream_name}");
        $this->assertResponseContains($response, $source_name);
        $this->assertResponseContains($response, "/streams/{$stream->id}/sources/feeds/new");
        $this->assertResponseTemplateName($response, 'streams/sources/edit.html.twig');
    }

    public function testEditGivesTheFocusToTheSourceOfTheFlash(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        /** @var string */
        $feed_url = $this->fake('url');
        $source = CollectionFactory::create([
            'type' => 'feed',
            'feed_site_url' => $feed_url,
            'is_public' => true,
        ]);
        $other_source = CollectionFactory::create([
            'type' => 'feed',
            'feed_site_url' => $feed_url,
            'is_public' => true,
        ]);
        $stream->addSource($source);
        $stream->addSource($other_source);
        \Minz\Flash::set('focused_source_id', $source->id);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'data-focus-target="item autofocus"');
        // The flash is popped, so the focus is not given again on the next
        // rendering of the page.
        $this->assertNull(\Minz\Flash::get('focused_source_id'));
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);
        /** @var string */
        $source_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $source = CollectionFactory::create([
            'type' => 'feed',
            'name' => $source_name,
            'feed_site_url' => $feed_url,
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fstreams%2F{$stream->id}%2Fsources%2Fedit");
    }

    public function testEditFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/streams/unknown/sources/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'name' => $stream_name,
        ]);
        /** @var string */
        $source_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $source = CollectionFactory::create([
            'type' => 'feed',
            'name' => $source_name,
            'feed_site_url' => $feed_url,
            'is_public' => true,
        ]);
        $other_user->follow($source->id);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testNewFeedRendersCorrectly(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/feeds/new");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'New feed');
        $this->assertResponseTemplateName($response, 'streams/sources/new_feed.html.twig');
    }

    public function testNewFeedRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/feeds/new");

        $redirect_to = urlencode("/streams/{$stream->id}/sources/feeds/new");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testNewFeedFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/streams/unknown/sources/feeds/new');

        $this->assertResponseCode($response, 404);
    }

    public function testNewFeedFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/sources/feeds/new");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateFeedCreatesTheFeedAndAddsItToTheStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertSame(1, models\Collection::countBy(['type' => 'feed']));
        $feed = models\Collection::findBy(['type' => 'feed']);
        $this->assertNotNull($feed);
        $this->assertSame($feed_url, $feed->feed_url);
        $this->assertTrue($user->isFollowing($feed->id));
        $this->assertTrue($stream->hasSource($feed));
        $this->assertSame($feed->id, \Minz\Flash::get('focused_source_id'));
    }

    public function testCreateFeedAutodetectsFeedUrls(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $url = 'https://flus.fr/carnet/';
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertSame(1, models\Collection::countBy(['type' => 'feed']));
        $feed = models\Collection::findBy(['type' => 'feed']);
        $this->assertNotNull($feed);
        $this->assertSame($feed_url, $feed->feed_url);
        $this->assertTrue($stream->hasSource($feed));
    }

    public function testCreateFeedDoesNotDuplicateAnExistingFeed(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'is_public' => true,
        ]);
        $user->follow($feed->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertSame(1, models\Collection::countBy(['type' => 'feed']));
        $this->assertTrue($stream->hasSource($feed));
    }

    public function testCreateFeedRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $redirect_to = urlencode("/streams/{$stream->id}/sources/feeds/new");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFeedFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', '/streams/unknown/sources/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFeedFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFeedFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => 'not the token',
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
        $this->assertSame([], $stream->sources());
    }

    public function testCreateFeedFailsIfUrlIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $feed_url = 'ftp://flus.fr/carnet/feeds/all.atom.xml';

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
        $this->assertSame([], $stream->sources());
    }

    public function testCreateFeedFailsIfNoFeedsCanBeFound(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        /** @var string */
        $feed_url = $this->fake('url');
        $this->mockHttpWithResponse($feed_url, <<<TEXT
            HTTP/2 200
            Content-type: text/html

            <html>
                <head>
                    <title>Hello World</title>
                </head>
                <body>This site has no feeds.</body>
            </html>
            TEXT
        );

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/feeds/new", [
            'csrf_token' => $this->csrfToken(forms\collections\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'There is no valid feeds at this address');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
        $this->assertSame([], $stream->sources());
    }

    public function testAddAddsTheSourceToTheStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $this->assertFalse($stream->hasSource($source));

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertTrue($stream->hasSource($source));
    }

    public function testAddWorksIfTheSourceIsNotFollowedYet(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $this->assertFalse($stream->hasSource($source));
        $this->assertFalse($user->isFollowing($source->id));

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertTrue($stream->hasSource($source));
        $this->assertTrue($user->isFollowing($source->id));
    }

    public function testAddRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($stream->hasSource($source));
    }

    public function testAddFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/unknown/sources/{$source->id}/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testAddFailsIfTheSourceDoesNotExist(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/unknown/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testAddFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($stream->hasSource($source));
    }

    public function testAddFailsIfTheUserCannotViewTheSource(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/add", [
            'csrf_token' => $this->csrfToken(forms\streams\AddSource::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($stream->hasSource($source));
    }

    public function testAddFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $this->assertFalse($stream->hasSource($source));

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/add", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
        $this->assertFalse($stream->hasSource($source));
    }

    public function testRemoveRemovesTheSourceFromTheStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $stream->addSource($source);

        $this->assertTrue($stream->hasSource($source));

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/remove", [
            'csrf_token' => $this->csrfToken(forms\streams\RemoveSource::class),
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertFalse($stream->hasSource($source));
    }

    public function testRemoveDoesNotFailsIfTheSourceIsNotInTheStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $this->assertFalse($stream->hasSource($source));

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/remove", [
            'csrf_token' => $this->csrfToken(forms\streams\RemoveSource::class),
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertFalse($stream->hasSource($source));
    }

    public function testRemoveRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/remove", [
            'csrf_token' => $this->csrfToken(forms\streams\RemoveSource::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($stream->hasSource($source));
    }

    public function testRemoveFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/streams/unknown/sources/{$source->id}/remove", [
            'csrf_token' => $this->csrfToken(forms\streams\RemoveSource::class),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testRemoveFailsIfTheSourceDoesNotExist(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/unknown/remove", [
            'csrf_token' => $this->csrfToken(forms\streams\RemoveSource::class),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testRemoveFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/remove", [
            'csrf_token' => $this->csrfToken(forms\streams\RemoveSource::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue($stream->hasSource($source));
    }

    public function testRemoveFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/{$source->id}/remove", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
        $this->assertTrue($stream->hasSource($source));
    }

    public function testAddAllAddsTheSourcesToTheStreamAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source_1->id);
        $user->follow($source_2->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source_1->id, $source_2->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($stream->hasSource($source_1));
        $this->assertTrue($stream->hasSource($source_2));
    }

    public function testAddAllDoesNotFailIfTheSourceIsAlreadyInTheStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($stream->hasSource($source));
    }

    public function testAddAllIgnoresNotFollowedSources(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($stream->hasSource($source));
        $this->assertFalse($user->isFollowing($source->id));
    }

    public function testAddAllDoesNothingIfSourceIdsIsEmpty(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($stream->hasSource($source));
    }

    public function testAddAllRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($stream->hasSource($source));
    }

    public function testAddAllFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', '/streams/unknown/sources/add', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testAddAllFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($stream->hasSource($source));
    }

    public function testAddAllFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/add", [
            'csrf_token' => 'not the token',
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
        $this->assertFalse($stream->hasSource($source));
    }

    public function testRemoveAllRemovesTheSourcesFromTheStreamAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source_1->id);
        $user->follow($source_2->id);
        $stream->addSource($source_1);
        $stream->addSource($source_2);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source_1->id, $source_2->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($stream->hasSource($source_1));
        $this->assertFalse($stream->hasSource($source_2));
    }

    public function testRemoveAllDoesNotUnfollowTheSources(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($stream->hasSource($source));
        $this->assertTrue($user->isFollowing($source->id));
    }

    public function testRemoveAllDoesNotTouchTheOtherStreams(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $other_stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);
        $stream->addSource($source);
        $other_stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($stream->hasSource($source));
        $this->assertTrue($other_stream->hasSource($source));
    }

    public function testRemoveAllDoesNotFailIfTheSourceIsNotInTheStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($stream->hasSource($source));
    }

    public function testRemoveAllRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($stream->hasSource($source));
    }

    public function testRemoveAllFailsIfTheStreamDoesNotExist(): void
    {
        $user = $this->login();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', '/streams/unknown/sources/remove', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testRemoveAllFailsIfTheUserCannotUpdateTheStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $other_user->follow($source->id);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue($stream->hasSource($source));
    }

    public function testRemoveAllFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source->id);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/sources/remove", [
            'csrf_token' => 'not the token',
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
        $this->assertTrue($stream->hasSource($source));
    }
}
