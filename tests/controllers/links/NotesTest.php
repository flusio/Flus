<?php

namespace App\controllers\links;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class NotesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRedirects(): void
    {
        $response = $this->appRun('GET', '/links/an_id/notes');

        $this->assertResponseCode($response, 302, '/links/an_id');
    }

    public function testCreateCreatesNoteAndRedirects(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Note::count());

        $response = $this->appRun('POST', "/links/{$link->id}/notes", [
            'csrf' => \App\Csrf::generate(),
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}");
        $this->assertSame(1, models\Note::count());
        $note = models\Note::take();
        $this->assertNotNull($note);
        $this->assertSame($content, $note->content);
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame($link->id, $note->link_id);
    }

    public function testCreateChangesLinkTags(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => [],
        ]);
        $content = '#foo #Bar';

        $this->assertSame(0, models\Note::count());

        $response = $this->appRun('POST', "/links/{$link->id}/notes", [
            'csrf' => \App\Csrf::generate(),
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}");
        $link = $link->reload();
        $this->assertEquals(['foo' => 'foo', 'bar' => 'Bar'], $link->tags);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'the token',
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = $this->fake('paragraphs', 3, true);

        $response = $this->appRun('POST', "/links/{$link->id}/notes", [
            'csrf' => \App\Csrf::generate(),
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}");
        $this->assertSame(0, models\Note::count());
    }

    public function testCreateFailsIfLinkIsNotOwned(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Note::count());

        $response = $this->appRun('POST', "/links/{$link->id}/notes", [
            'csrf' => \App\Csrf::generate(),
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Note::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Note::count());

        $response = $this->appRun('POST', "/links/{$link->id}/notes", [
            'csrf' => 'not the token',
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Note::count());
    }

    public function testCreateFailsIfContentIsEmpty(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = '';

        $this->assertSame(0, models\Note::count());

        $response = $this->appRun('POST', "/links/{$link->id}/notes", [
            'csrf' => \App\Csrf::generate(),
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The content is required');
        $this->assertSame(0, models\Note::count());
    }
}
