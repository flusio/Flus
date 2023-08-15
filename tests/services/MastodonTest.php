<?php

namespace flusio\services;

use flusio\models;
use tests\factories\LinkFactory;
use tests\factories\MessageFactory;

class MastodonTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;

    /**
     * @beforeClass
     */
    public static function initEngine(): void
    {
        $router = \flusio\Router::load();
        \Minz\Engine::init($router);
    }

    public function testFormatStatus(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $message = MessageFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$comments_url}

            This is great content!

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNeverLinkToComments(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $message = MessageFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'never',
            'post_scriptum' => '#flus',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr

            This is great content!

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNoMessage(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $message = null;
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNoMessageAndAlwaysLinkToComment(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $message = null;
        $options = [
            'link_to_comment' => 'always',
            'post_scriptum' => '#flus',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$comments_url}

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithNoPostScriptum(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $message = MessageFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$comments_url}

            This is great content!
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithALongTitle(): void
    {
        $link = LinkFactory::create([
            'title' => str_repeat('a', 260),
            'url' => 'https://flus.fr',
        ]);
        $message = MessageFactory::create([
            'content' => 'This is great content!',
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_title = str_repeat('a', 249) . '…';
        $expected_status = <<<"TEXT"
            {$expected_title}

            https://flus.fr
            {$comments_url}

            This is great content!

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }

    public function testFormatStatusWithALongMessage(): void
    {
        $link = LinkFactory::create([
            'title' => 'My title',
            'url' => 'https://flus.fr',
        ]);
        $message = MessageFactory::create([
            'content' => str_repeat('a', 500),
        ]);
        $options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '#flus',
        ];
        $comments_url = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $expected_comment = str_repeat('a', 433) . '…';
        $expected_status = <<<"TEXT"
            My title

            https://flus.fr
            {$comments_url}

            {$expected_comment}

            #flus
            TEXT;

        $status = Mastodon::formatStatus($link, $message, $options);

        $this->assertSame($expected_status, $status);
    }
}
