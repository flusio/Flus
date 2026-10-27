<?php

namespace App\services;

use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class OpmlGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setRouterToUrl(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    public function testRenderFollowsRendersAllTheSourcesForTheUserThemselves(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create([
            'username' => 'Alix Hambourg',
        ]);
        $private_stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Private stream',
            'is_public' => false,
        ]);
        $source_private_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source of the private stream',
            'is_public' => true,
        ]);
        $source_no_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source without stream',
            'is_public' => true,
        ]);
        $user->follow($source_private_stream);
        $user->follow($source_no_stream);
        $private_stream->addSource($source_private_stream);

        $opml_content = $opml_generator->renderFollows($user, [
            'context_user' => $user,
        ]);

        $this->assertStringContainsString('<title>Alix Hambourg’s sources</title>', $opml_content);
        $opml = \SpiderBits\Opml::fromText($opml_content);
        $this->assertSame(2, count($opml->outlines));
        $this->assertSame('Source without stream', $opml->outlines[0]['text']);
        $this->assertSame('Private stream', $opml->outlines[1]['text']);
        $this->assertIsArray($opml->outlines[1]['outlines']);
        $this->assertSame(1, count($opml->outlines[1]['outlines']));
        $this->assertIsArray($opml->outlines[1]['outlines'][0]);
        $this->assertSame('Source of the private stream', $opml->outlines[1]['outlines'][0]['text']);
    }

    public function testRenderFollowsOnlyRendersWhatTheContextUserCanView(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $context_user = UserFactory::create();
        $public_stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Public stream',
            'is_public' => true,
        ]);
        $shared_stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Shared stream',
            'is_public' => false,
        ]);
        $private_stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Private stream',
            'is_public' => false,
        ]);
        $shared_stream->shareWith($context_user);
        $source_public_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source of the public stream',
            'is_public' => true,
        ]);
        $source_shared_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source of the shared stream',
            'is_public' => true,
        ]);
        $source_private_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source of the private stream',
            'is_public' => true,
        ]);
        $source_no_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source without stream',
            'is_public' => true,
        ]);
        $user->follow($source_public_stream);
        $user->follow($source_shared_stream);
        $user->follow($source_private_stream);
        $user->follow($source_no_stream);
        $public_stream->addSource($source_public_stream);
        $shared_stream->addSource($source_shared_stream);
        $private_stream->addSource($source_private_stream);

        $opml = $opml_generator->renderFollows($user, [
            'context_user' => $context_user,
        ]);

        $this->assertStringContainsString('Public stream', $opml);
        $this->assertStringContainsString('Source of the public stream', $opml);
        $this->assertStringContainsString('Shared stream', $opml);
        $this->assertStringContainsString('Source of the shared stream', $opml);
        $this->assertStringNotContainsString('Private stream', $opml);
        $this->assertStringNotContainsString('Source of the private stream', $opml);
        $this->assertStringNotContainsString('Source without stream', $opml);
    }

    public function testRenderFollowsOnlyRendersThePublicStreamsWithoutContextUser(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $public_stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Public stream',
            'is_public' => true,
        ]);
        $private_stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Private stream',
            'is_public' => false,
        ]);
        $source_no_stream = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Source without stream',
            'is_public' => true,
        ]);
        $user->follow($source_no_stream);

        $opml = $opml_generator->renderFollows($user);

        $this->assertStringContainsString('Public stream', $opml);
        $this->assertStringNotContainsString('Private stream', $opml);
        $this->assertStringNotContainsString('Source without stream', $opml);
    }

    public function testRenderFollowsUsesTheNamesGivenByTheContextUser(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $context_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Carnet de Flus',
            'is_public' => true,
        ]);
        $user->follow($source);
        $stream->addSource($source);
        $followed_collection = $context_user->follow($source);
        $followed_collection->name = 'Flus news';
        $followed_collection->save();

        $opml = $opml_generator->renderFollows($user, [
            'context_user' => $context_user,
        ]);

        $this->assertStringContainsString('Flus news', $opml);
        $this->assertStringNotContainsString('Carnet de Flus', $opml);
    }

    public function testRenderFollowsRendersTheTimeFiltersOnlyIfRequested(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($source, time_filter: 'strict');

        $opml_with_time_filter = $opml_generator->renderFollows($user, [
            'context_user' => $user,
            'with_time_filter' => true,
        ]);
        $opml_without_time_filter = $opml_generator->renderFollows($user, [
            'context_user' => $user,
        ]);

        $this->assertStringContainsString('/Flus/filters/strict', $opml_with_time_filter);
        $this->assertStringNotContainsString('/Flus/filters/strict', $opml_without_time_filter);
    }

    public function testRenderCollectionsRendersThePublicCollectionsWithoutContextUser(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create([
            'username' => 'Alix Hambourg',
        ]);
        CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => 'Public collection',
            'is_public' => true,
        ]);
        CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => 'Private collection',
            'is_public' => false,
        ]);

        $opml = $opml_generator->renderCollections($user);

        $this->assertStringContainsString('<title>Alix Hambourg’s collections</title>', $opml);
        $this->assertStringContainsString('Public collection', $opml);
        $this->assertStringNotContainsString('Private collection', $opml);
    }

    public function testRenderCollectionsRendersTheCollectionsSharedWithTheContextUser(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $context_user = UserFactory::create();
        $shared_collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => 'Shared collection',
            'is_public' => false,
        ]);
        CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => 'Private collection',
            'is_public' => false,
        ]);
        $shared_collection->shareWith($context_user, 'read');

        $opml = $opml_generator->renderCollections($user, [
            'context_user' => $context_user,
        ]);

        $this->assertStringContainsString('Shared collection', $opml);
        $this->assertStringNotContainsString('Private collection', $opml);
    }

    public function testRenderStreamRendersThePublicSourcesWithoutContextUser(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'My stream',
            'is_public' => true,
        ]);
        $public_source = CollectionFactory::create([
            'type' => 'collection',
            'name' => 'Public source',
            'is_public' => true,
        ]);
        $private_source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => 'Private source',
            'is_public' => false,
        ]);
        $stream->addSource($public_source);
        $stream->addSource($private_source);

        $opml_content = $opml_generator->renderStream($stream);

        $this->assertStringContainsString('<title>My stream</title>', $opml_content);
        $this->assertStringNotContainsString('Private source', $opml_content);
        $opml = \SpiderBits\Opml::fromText($opml_content);
        $this->assertSame(1, count($opml->outlines));
        $this->assertSame('My stream', $opml->outlines[0]['text']);
        $this->assertIsArray($opml->outlines[0]['outlines']);
        $this->assertSame(1, count($opml->outlines[0]['outlines']));
        $this->assertIsArray($opml->outlines[0]['outlines'][0]);
        $this->assertSame('Public source', $opml->outlines[0]['outlines'][0]['text']);
    }

    public function testRenderStreamRendersTheSourcesTheContextUserCanView(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $private_source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => 'Private source',
            'is_public' => false,
        ]);
        $stream->addSource($private_source);

        $opml = $opml_generator->renderStream($stream, [
            'context_user' => $user,
        ]);

        $this->assertStringContainsString('Private source', $opml);
    }

    public function testRenderStreamUsesTheNamesGivenByTheContextUser(): void
    {
        $opml_generator = new OpmlGenerator();
        $user = UserFactory::create();
        $context_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'name' => 'Carnet de Flus',
            'is_public' => true,
        ]);
        $stream->addSource($source);
        $followed_collection = $context_user->follow($source);
        $followed_collection->name = 'Flus news';
        $followed_collection->save();

        $opml = $opml_generator->renderStream($stream, [
            'context_user' => $context_user,
        ]);

        $this->assertStringContainsString('Flus news', $opml);
        $this->assertStringNotContainsString('Carnet de Flus', $opml);
    }
}
