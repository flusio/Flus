<?php

namespace App\controllers;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class StreamsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

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
        ]);

        $this->assertSame(1, models\Stream::count());
        $stream = models\Stream::take();
        $this->assertNotNull($stream);
        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/sources/edit");
        $this->assertSame($name, $stream->name);
        $this->assertSame($description, $stream->description);
        $this->assertFalse($stream->is_public);
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
        $this->assertResponseTemplateName($response, 'streams/show.html.twig');
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

    public function testShowFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/streams/unknown');

        $this->assertResponseCode($response, 404);
    }
}
