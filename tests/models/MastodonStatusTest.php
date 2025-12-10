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

    public function testBuildDefaultContent(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
            'is_hidden' => false,
        ]);
        $options = [
            'prefill_with_notes' => false,
            'link_to_notes' => false,
            'post_scriptum' => '',
            'post_scriptum_in_all_posts' => false,
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testBuildDefaultContentWithLinkToNotes(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
            'is_hidden' => false,
        ]);
        $options = [
            'prefill_with_notes' => false,
            'link_to_notes' => true,
            'post_scriptum' => '',
            'post_scriptum_in_all_posts' => false,
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link);
        $notes_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            {$notes_url}
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testBuildDefaultContentWithLinkToNotesButHiddenLink(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
            'is_hidden' => true,
        ]);
        $options = [
            'prefill_with_notes' => false,
            'link_to_notes' => true,
            'post_scriptum' => '',
            'post_scriptum_in_all_posts' => false,
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testBuildDefaultContentWithPostScriptum(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
            'is_hidden' => false,
        ]);
        $options = [
            'prefill_with_notes' => false,
            'link_to_notes' => false,
            'post_scriptum' => '#flus',
            'post_scriptum_in_all_posts' => false,
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $status = new MastodonStatus($account, $link);
        $link_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_content = <<<"TEXT"
            My title

            https://flus.fr

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testBuildContent(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
            'is_hidden' => false,
        ]);
        $options = [
            'prefill_with_notes' => false,
            'link_to_notes' => false,
            'post_scriptum' => '',
            'post_scriptum_in_all_posts' => false,
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $content = 'This is very interesting!';
        $status = new MastodonStatus($account, $link, $content);
        $expected_content = <<<"TEXT"
            {$content}
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }

    public function testBuildContentWithPostScriptum(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
            'is_hidden' => false,
        ]);
        $options = [
            'prefill_with_notes' => false,
            'link_to_notes' => false,
            'post_scriptum' => '#flus',
            'post_scriptum_in_all_posts' => true,
        ];
        $account = MastodonAccountFactory::create([
            'options' => $options,
        ]);
        $content = 'This is very interesting!';
        $status = new MastodonStatus($account, $link, $content);
        $expected_content = <<<"TEXT"
            {$content}

            #flus
            TEXT;

        $this->assertSame($expected_content, $status->content);
    }
}
