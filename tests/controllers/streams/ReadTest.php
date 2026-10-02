<?php

namespace App\controllers\streams;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testCreateMarksLinksAsReadAndRedirects(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
        $links = models\Link::listBy([
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, count($links));
        $new_link = $links[0];
        $this->assertSame($link->url, $new_link->url);
        $this->assertSame($source->id, $new_link->source_id);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $source->id]);
        $this->assertSame($origin, $new_link->origin);
    }

    public function testCreateSkipsDismissedLinks(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $user->markAsDismissed($link);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testCreateMarksDismissedLinksAsReadIfRequested(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $user->markAsDismissed($link);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'with_dismissed' => '1',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
    }

    public function testCreateRemovesLinksFromJournal(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $journal = $user->journal();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $journal_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link->url,
        ]);
        $journal->addLinks([$journal_link]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
        $this->assertFalse($journal->hasLink($journal_link), 'The link should not be in the journal.');
    }

    public function testCreateMarksLinksAsReadForSpecificDate(): void
    {
        $date1 = new \DateTimeImmutable('2024-03-25');
        $date2 = new \DateTimeImmutable('2024-03-26');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link1], at: $date1);
        $source->addLinks([$link2], at: $date2);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date1->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertFalse($user->hasRead($link2), 'The link should not be read.');
    }

    public function testCreateMarksLinksAsReadOverSeveralDays(): void
    {
        $date1 = new \DateTimeImmutable('2024-03-25');
        $date2 = new \DateTimeImmutable('2024-03-24');
        $date3 = new \DateTimeImmutable('2024-03-23');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link1], at: $date1);
        $source->addLinks([$link2], at: $date2);
        $source->addLinks([$link3], at: $date3);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date1->format('Y-m-d'),
            'days' => 2,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertTrue($user->hasRead($link2), 'The link should be read.');
        $this->assertFalse($user->hasRead($link3), 'The link should not be read.');
    }

    public function testCreateMarksLinksAsReadForSpecificSources(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source3 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source1->addLinks([$link1], at: $date);
        $source2->addLinks([$link2], at: $date);
        $source3->addLinks([$link3], at: $date);
        $stream->addSource($source1);
        $stream->addSource($source2);
        $stream->addSource($source3);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'sources' => [$source1->id, $source2->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertTrue($user->hasRead($link2), 'The link should be read.');
        $this->assertFalse($user->hasRead($link3), 'The link should not be read.');
    }

    public function testCreateDoesNotMarkLinksIfNoSourcesAreViewableSourcesOfTheStream(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        // The source is neither in the stream, nor viewable by the user, but
        // its id is passed directly as a parameter of the request.
        $source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'sources' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testCreateIgnoresTheSourcesThatTheUserCannotView(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $stream_source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $other_stream_source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        // The source is a private collection of the owner of the stream: the
        // user the stream is shared with cannot view it.
        $private_source = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $stream_link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $other_stream_link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $private_link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $stream_source->addLinks([$stream_link], at: $date);
        $other_stream_source->addLinks([$other_stream_link], at: $date);
        $private_source->addLinks([$private_link], at: $date);
        $stream->addSource($stream_source);
        $stream->addSource($other_stream_source);
        $stream->addSource($private_source);
        $stream->shareWith($user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'sources' => [$stream_source->id, $private_source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($stream_link), 'The link should be read.');
        $this->assertFalse($user->hasRead($other_stream_link), 'The link should not be read.');
        $this->assertFalse($user->hasRead($private_link), 'The link should not be read.');
    }

    public function testCreateMarksLinksAsReadForSpecificStatus(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link1, $link2], at: $date);
        $stream->addSource($source);
        $user->markAsReadLater($link1);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'status' => 'read-later',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertFalse($user->hasRead($link2), 'The link should not be read.');
    }

    public function testCreateMarksLinksAsReadForSpecificQuery(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'title' => 'Why streams are better than foos?',
            'url' => 'https://example.com/article',
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'title' => 'Another subject',
            'url' => 'https://example.com/other',
            'is_hidden' => false,
        ]);
        $source->addLinks([$link1, $link2], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'q' => 'foos',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertFalse($user->hasRead($link2), 'The link should not be read.');
    }

    public function testCreateDoesNotMarkLinksIfQueryIsMalformed(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'q' => 'duration:abc',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testCreateDoesNotMarkLinksCreatedAfterBefore(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
            'created_at' => \Minz\Time::ago(1, 'hour'),
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
            'created_at' => \Minz\Time::fromNow(1, 'hour'),
        ]);
        $source->addLinks([$link1, $link2], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
            'before' => \Minz\Time::now()->format('Y-m-d H:i:sP'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $this->assertFalse($user->hasRead($link2), 'The link should not be read.');
    }

    public function testCreateDeduplicatesLinksWithSameUrl(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $url = 'https://example.com/same-link';
        $link1 = LinkFactory::create([
            'is_hidden' => false,
            'url' => $url,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
            'url' => $url,
        ]);
        $source1->addLinks([$link1], at: $date);
        $source2->addLinks([$link2], at: $date);
        $stream->addSource($source1);
        $stream->addSource($source2);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link1), 'The link should be read.');
        $links = models\Link::listBy([
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, count($links));
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testCreateWorksIfStreamIsPublicAndNotOwned(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
    }

    public function testCreateWorksIfStreamIsSharedWithUser(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream->shareWith($user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasRead($link), 'The link should be read.');
    }

    public function testCreateFailsIfStreamIsInaccessible(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsRead::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read", [
            'csrf_token' => 'not the token',
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
    }

    public function testLaterMarksLinksToReadLaterAndRedirects(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsReadLater::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link), 'The link should be to read later.');
        $links = models\Link::listBy([
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, count($links));
        $new_link = $links[0];
        $this->assertSame($link->url, $new_link->url);
    }

    public function testLaterMarksLinksToReadLaterForSpecificDate(): void
    {
        $date1 = new \DateTimeImmutable('2024-03-25');
        $date2 = new \DateTimeImmutable('2024-03-26');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link1], at: $date1);
        $source->addLinks([$link2], at: $date2);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsReadLater::class),
            'at' => $date1->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link1), 'The link should be to read later.');
        $this->assertFalse($user->hasReadLater($link2), 'The link should not be to read later.');
    }

    public function testLaterRedirectsToLoginIfNotConnected(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsReadLater::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
    }

    public function testLaterWorksIfStreamIsSharedWithUser(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream->shareWith($user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsReadLater::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasReadLater($link), 'The link should be to read later.');
    }

    public function testLaterFailsIfStreamIsInaccessible(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read/later", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsReadLater::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
    }

    public function testLaterFailsIfCsrfIsInvalid(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/read/later", [
            'csrf_token' => 'not the token',
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasReadLater($link), 'The link should not be to read later.');
    }

    public function testDismissMarksLinksAsDismissedAndRedirects(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsDismissed::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link), 'The link should have been dismissed.');
        $this->assertFalse($user->hasRead($link), 'The link should not be read.');
        // Link is not copied in case of dismissing.
        $this->assertSame(0, models\Link::countBy([
            'user_id' => $user->id,
        ]));
    }

    public function testDismissRemovesLinksFromJournalByUrl(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $journal = $user->journal();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $journal_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link->url,
        ]);
        $journal->addLinks([$journal_link]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsDismissed::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link), 'The link should have been dismissed.');
        $this->assertFalse($journal->hasLink($journal_link), 'The link should not be in the journal.');
    }

    public function testDismissMarksLinksAsDismissedForSpecificDate(): void
    {
        $date1 = new \DateTimeImmutable('2024-03-25');
        $date2 = new \DateTimeImmutable('2024-03-26');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link1], at: $date1);
        $source->addLinks([$link2], at: $date2);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsDismissed::class),
            'at' => $date1->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link1), 'The link should have been dismissed.');
        $this->assertFalse($user->hasDismissed($link2), 'The link should not have been dismissed.');
    }

    public function testDismissRedirectsToLoginIfNotConnected(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsDismissed::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertFalse($user->hasDismissed($link), 'The link should not have been dismissed.');
    }

    public function testDismissWorksIfStreamIsSharedWithUser(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream->shareWith($user);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsDismissed::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->hasDismissed($link), 'The link should have been dismissed.');
    }

    public function testDismissFailsIfStreamIsInaccessible(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => $this->csrfToken(forms\streams\MarkStreamAsDismissed::class),
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->hasDismissed($link), 'The link should not have been dismissed.');
    }

    public function testDismissFailsIfCsrfIsInvalid(): void
    {
        $date = new \DateTimeImmutable('2024-03-25');
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);

        $response = $this->appRun('POST', "/streams/{$stream->id}/dismiss", [
            'csrf_token' => 'not the token',
            'at' => $date->format('Y-m-d'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertFalse($user->hasDismissed($link), 'The link should not have been dismissed.');
    }
}
