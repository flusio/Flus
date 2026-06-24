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
    use \tests\LoginHelper;

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
        $this->assertResponseTemplateName($response, 'streams/sources/edit.html.twig');
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
}
