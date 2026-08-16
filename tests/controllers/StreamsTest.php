<?php

namespace App\controllers;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;
use tests\factories\ViewFactory;

class StreamsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\SqlQueriesHelper;

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/streams/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'New stream');
        $this->assertResponseTemplateName($response, 'streams/new.html.twig');
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/streams/new');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fstreams%2Fnew');
    }

    public function testCreateCreatesStreamAndRedirects(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $this->assertSame(0, models\Stream::count());

        $response = $this->appRun('POST', '/streams/new', [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => $name,
            'description' => $description,
            'display_unread_in_sidenav' => true,
        ]);

        $this->assertSame(1, models\Stream::count());
        $stream = models\Stream::take();
        $this->assertNotNull($stream);
        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertSame($name, $stream->name);
        $this->assertSame($description, $stream->description);
        $this->assertFalse($stream->is_public);
        $this->assertTrue($stream->display_unread_in_sidenav);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/streams/new', [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fstreams%2Fnew');
        $this->assertSame(0, models\Stream::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/streams/new', [
            'csrf_token' => 'not the token',
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Stream::count());
    }

    public function testCreateFailsIfNameIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 100, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/streams/new', [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters');
        $this->assertSame(0, models\Stream::count());
    }

    public function testCreateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/streams/new', [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
        $this->assertSame(0, models\Stream::count());
    }

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($feed);

        $response = $this->appRun('GET', "/streams/{$stream->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseContains($response, "/streams/{$stream->id}/sources");
        $this->assertResponseContains($response, '1 source');
        $this->assertResponseTemplateName($response, 'streams/show.html.twig');
    }

    public function testShowExecutesAConstantNumberOfQueries(): void
    {
        $user = $this->login();
        models\FeatureFlag::enable('alpha', $user->id);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        foreach (range(1, 3) as $index_source) {
            $feed = CollectionFactory::create([
                'type' => 'feed',
                'is_public' => true,
            ]);
            $links = [];

            foreach (range(1, 10) as $index_link) {
                $links[] = LinkFactory::create([
                    'user_id' => $feed->user_id,
                    'is_hidden' => false,
                ]);
            }

            $feed->addLinks($links, at: \Minz\Time::now());
            $stream->addSource($feed);
        }

        list($response, $count_queries) = $this->countSqlQueries(function () use ($stream): \Minz\Response {
            $response = $this->appRun('GET', "/streams/{$stream->id}");

            // The templates are rendered lazily, so the response must be
            // rendered here for the queries of the views to be counted.
            $this->assertInstanceOf(\Minz\Response::class, $response);
            $response->render();

            return $response;
        });

        $this->assertResponseCode($response, 200);
        // The number of queries must not grow with the number of links: the
        // data that the links need is loaded in batch by models\links\Preloader
        // (see models\StreamView::linksTimeline()).
        $this->assertLessThanOrEqual(20, $count_queries);
    }

    public function testShowHidesHiddenLinksInPublicCollections(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'is_hidden' => true,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowHidesDismissedLinks(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($feed);
        $user->markAsDismissed($link);

        $response = $this->appRun('GET', "/streams/{$stream->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowDisplaysDismissedLinksIfRequested(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($feed);
        $user->markAsDismissed($link);

        $response = $this->appRun('GET', "/streams/{$stream->id}", [
            'with_dismissed' => '1',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
    }

    public function testShowDisplaysTheViewsBar(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        ViewFactory::create([
            'stream_id' => $stream->id,
            'user_id' => $user->id,
            'name' => 'My unreads',
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Main view');
        $this->assertResponseContains($response, 'My unreads');
    }

    public function testShowAppliesTheDefaultViewOnABareUrl(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        ViewFactory::create([
            'stream_id' => $stream->id,
            'user_id' => $user->id,
            'is_default' => true,
            'parameters' => [
                'at_offset' => '0',
                'days' => '1',
                'source' => '',
                'status' => 'all',
                'with_dismissed' => '',
                'q' => 'a very specific query',
            ],
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'value="a very specific query"');
    }

    public function testShowFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/streams/unknown');

        $this->assertResponseCode($response, 404);
    }

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Stream edition');
        $this->assertResponseTemplateName($response, 'streams/edit.html.twig');
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fstreams%2F{$stream->id}%2Fedit");
    }

    public function testEditFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/streams/unknown/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testUpdateUpdatesStreamAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Old name',
            'description' => 'Old description',
        ]);
        /** @var string */
        $new_name = $this->fake('words', 3, true);
        /** @var string */
        $new_description = $this->fake('sentence');

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/edit");
        $stream = $stream->reload();
        $this->assertSame($new_name, $stream->name);
        $this->assertSame($new_description, $stream->description);
    }

    public function testUpdateDisablesDisplayUnreadInSidenav(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'display_unread_in_sidenav' => true,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => $stream->name,
            'description' => $stream->description,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/edit");
        $stream = $stream->reload();
        $this->assertFalse($stream->display_unread_in_sidenav);
    }

    public function testUpdateEnablesDisplayUnreadInSidenav(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'display_unread_in_sidenav' => false,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => $stream->name,
            'description' => $stream->description,
            'display_unread_in_sidenav' => true,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/edit");
        $stream = $stream->reload();
        $this->assertTrue($stream->display_unread_in_sidenav);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Old name',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => 'New name',
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fstreams%2F{$stream->id}%2Fedit");
        $stream = $stream->reload();
        $this->assertSame('Old name', $stream->name);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Old name',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => 'not the token',
            'name' => 'New name',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $stream = $stream->reload();
        $this->assertSame('Old name', $stream->name);
    }

    public function testUpdateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Old name',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
        $stream = $stream->reload();
        $this->assertSame('Old name', $stream->name);
    }

    public function testUpdateFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'name' => 'Old name',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\streams\Stream::class),
            'name' => 'New name',
        ]);

        $this->assertResponseCode($response, 403);
        $stream = $stream->reload();
        $this->assertSame('Old name', $stream->name);
    }

    public function testDeleteDeletesStreamAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\streams\DeleteStream::class),
        ]);

        $this->assertResponseCode($response, 302, '/news');
        $this->assertFalse(models\Stream::exists($stream->id));
        $success = utils\Notification::popSuccess();
        $this->assertStringContainsString('The stream has been deleted.', $success);
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\streams\DeleteStream::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Stream::exists($stream->id));
    }

    public function testDeleteFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('POST', '/streams/unknown/delete', [
            'csrf_token' => $this->csrfToken(forms\streams\DeleteStream::class),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testDeleteFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\streams\DeleteStream::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue(models\Stream::exists($stream->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/delete", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue(models\Stream::exists($stream->id));
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
    }
}
