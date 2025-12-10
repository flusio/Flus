<?php

namespace App\models;

use tests\factories\LinkFactory;
use tests\factories\MastodonAccountFactory;
use tests\factories\MastodonServerFactory;
use tests\factories\NoteFactory;

class MastodonStatusTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initEngine(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    public function testDefaultContent(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = NoteFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            This is great content!

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithNeverLinkToNotes(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = NoteFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'never',
            'post_scriptum' => '#flus',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr

            This is great content!

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithNoNote(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = null;
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithNoNoteAndAlwaysLinkToNote(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = null;
        $options = [
            'link_to_comment' => 'always',
            'post_scriptum' => '#flus',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithNoPostScriptum(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = NoteFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            This is great content!
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithALongTitle(): void
    {
        $link = LinkFactory::create([
            'title' => str_repeat('a', 260),
            'url' => 'https://flus.fr',
        ]);
        $note = NoteFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_title = str_repeat('a', 249) . '…';
        $expected_content = <<<"TEXT"
            {$expected_title}

            https://flus.fr
            {$notes_url}

            This is great content!

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithALongNote(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = NoteFactory::create([
            'content' => str_repeat('a', 500),
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_note = str_repeat('a', 433) . '…';
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            {$expected_note}

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testDefaultContentWithShorterServerStatusesMaxChars(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $note = NoteFactory::create([
            'content' => str_repeat('a', 500),
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $server = MastodonServerFactory::create([
            'statuses_max_characters' => 250,
        ]);
        $account = MastodonAccountFactory::create([
            'mastodon_server_id' => $server->id,
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link, $note);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_note = str_repeat('a', 183) . '…';
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            {$expected_note}

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }
}
