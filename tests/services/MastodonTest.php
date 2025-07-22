<?php

namespace App\services;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\NoteFactory;

class MastodonTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initEngine(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    public function testFormatStatus(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            This is great content!

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNeverLinkToNotes(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr

            This is great content!

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNoNote(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNoNoteAndAlwaysLinkToNote(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNoPostScriptum(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            This is great content!
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithALongTitle(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_title = str_repeat('a', 249) . '…';
        $expected_status = <<<"TEXT"
            {$expected_title}

            https://flus.fr
            {$notes_url}

            This is great content!

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithALongNote(): void
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
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_note = str_repeat('a', 433) . '…';
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}

            {$expected_note}

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $note, $options);

        $this->assertSame($expected_status, $status);
    }
}
