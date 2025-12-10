<?php

namespace App\controllers\links;

use App\forms;
use App\jobs;
use App\models;
use tests\factories\LinkFactory;
use tests\factories\MastodonAccountFactory;
use tests\factories\NoteFactory;
use tests\factories\UserFactory;

class MastodonThreadsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\LoginHelper;

    #[\PHPUnit\Framework\Attributes\Before]
    public function changeJobsAdapterToDatabase(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function changeJobsAdapterToTest(): void
    {
        \App\Configuration::$jobs_adapter = 'test';
    }

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Share a link on Mastodon');
    }

    public function testNewPrefillsWithNotes(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = 'This is the content of my note';
        $note = NoteFactory::create([
            'link_id' => $link->id,
            'user_id' => $user->id,
            'content' => $content,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
            'options' => [
                'prefill_with_notes' => true,
                'link_to_notes' => true,
                'post_scriptum' => '',
                'post_scriptum_in_all_posts' => false,
            ],
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $content);
    }

    public function testNewCreatesALinkOwnedByTheUserIfNecessary(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => false,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon");

        $this->assertSame(2, models\Link::count());

        $new_link = models\Link::take(1);
        $this->assertNotNull($new_link);
        $this->assertSame($link->url, $new_link->url);
        $this->assertSame($user->id, $new_link->user_id);
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $new_link->id);
    }

    public function testNewRedirectsIfUserIsNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fshares%2Fmastodon");
    }

    public function testNewFailsIfLinkDoesNotExist(): void
    {
        $user = $this->login();
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', '/links/not-an-id/shares/mastodon');

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfUserDoesNotHaveAccessToLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => true,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateCreatesMastodonStatusesAndRedirects(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', "/links/{$link->id}/shares/mastodon", [
            'contents' => [
                'This is a post.',
                'This is a second post.',
            ],
            'csrf_token' => $this->csrfToken(forms\links\MastodonThread::class),
        ]);

        $this->assertSame(2, models\MastodonStatus::count());
        $this->assertSame(1, jobs\ShareOnMastodon::count());

        $this->assertResponseCode($response, 302, "/links/{$link->id}/shares/mastodon/created");
        $first_status = models\MastodonStatus::take(0);
        $second_status = models\MastodonStatus::take(1);
        $job = jobs\ShareOnMastodon::take();
        $this->assertNotNull($first_status);
        $this->assertNotNull($second_status);
        $this->assertNotNull($job);
        $this->assertSame('This is a post.', $first_status->content);
        $this->assertSame($link->id, $first_status->link_id);
        $this->assertSame('This is a second post.', $second_status->content);
        $this->assertSame($link->id, $second_status->link_id);
        $this->assertSame($first_status->id, $second_status->reply_to_id);
        $this->assertSame($first_status->id, $job->args[0]);
    }

    public function testCreateCreatesALinkOwnedByTheUserIfNecessary(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => false,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', "/links/{$link->id}/shares/mastodon", [
            'contents' => [
                'This is a post.',
            ],
            'csrf_token' => $this->csrfToken(forms\links\MastodonThread::class),
        ]);

        $this->assertSame(2, models\Link::count());
        $this->assertSame(1, models\MastodonStatus::count());
        $this->assertSame(1, jobs\ShareOnMastodon::count());

        $new_link = models\Link::take(1);
        $status = models\MastodonStatus::take(0);
        $this->assertNotNull($new_link);
        $this->assertNotNull($status);
        $this->assertSame($link->url, $new_link->url);
        $this->assertSame($user->id, $new_link->user_id);
        $this->assertSame($new_link->id, $status->link_id);
        $this->assertResponseCode($response, 302, "/links/{$new_link->id}/shares/mastodon/created");
    }

    public function testCreateIgnoresEmptyContents(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', "/links/{$link->id}/shares/mastodon", [
            'contents' => [
                '',
            ],
            'csrf_token' => $this->csrfToken(forms\links\MastodonThread::class),
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $this->assertResponseCode($response, 302, "/links/{$link->id}/shares/mastodon/created");
    }

    public function testCreateRedirectsIfUserIsNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', "/links/{$link->id}/shares/mastodon", [
            'contents' => [
                'This is a post.',
            ],
            'csrf_token' => $this->csrfToken(forms\links\MastodonThread::class),
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fshares%2Fmastodon");
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', "/links/{$link->id}/shares/mastodon", [
            'contents' => [
                'This is a post.',
                'This is a second post.',
            ],
            'csrf_token' => 'not a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
    }

    public function testCreateFailsIfLinkDoesNotExist(): void
    {
        $user = $this->login();
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', '/links/not-an-id/shares/mastodon', [
            'contents' => [
                'This is a post.',
            ],
            'csrf_token' => $this->csrfToken(forms\links\MastodonThread::class),
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $this->assertResponseCode($response, 404);
    }

    public function testCreateFailsIfUserDoesNotHaveAccessToLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => true,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $response = $this->appRun('POST', "/links/{$link->id}/shares/mastodon", [
            'contents' => [
                'This is a post.',
            ],
            'csrf_token' => $this->csrfToken(forms\links\MastodonThread::class),
        ]);

        $this->assertSame(0, models\MastodonStatus::count());
        $this->assertSame(0, jobs\ShareOnMastodon::count());

        $this->assertResponseCode($response, 403);
    }

    public function testCreatedRendersCorrectly(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon/created");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your posts are going to be published on Mastodon!');
    }

    public function testCreatedRedirectsIfUserIsNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon/created");

        $this->assertResponseCode(
            $response,
            302,
            "/login?redirect_to=%2Flinks%2F{$link->id}%2Fshares%2Fmastodon%2Fcreated"
        );
    }

    public function testCreatedFailsIfLinkDoesNotExist(): void
    {
        $user = $this->login();
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', '/links/not-an-id/shares/mastodon/created');

        $this->assertResponseCode($response, 404);
    }

    public function testCreatedFailsIfUserDoesNotHaveAccessToLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => true,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/shares/mastodon/created");

        $this->assertResponseCode($response, 403);
    }
}
