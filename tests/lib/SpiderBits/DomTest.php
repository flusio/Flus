<?php

namespace SpiderBits;

class DomTest extends \PHPUnit\Framework\TestCase
{
    public function testText(): void
    {
        $dom = Dom::fromText(<<<HTML
            <title>Hello World!</title>
        HTML);

        $text = $dom->text();

        $this->assertSame('Hello World!', $text);
    }

    public function testTextWithMixOfHtmlEntitiesAndUtf8(): void
    {
        $dom = Dom::fromText(<<<HTML
            <title>Site d&#039;information français</title>
        HTML);

        $text = $dom->text();

        $this->assertSame("Site d'information français", $text);
    }

    public function testHtml(): void
    {
        $dom = Dom::fromText(<<<HTML
            <div>Hello World!</div>
        HTML, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $html = $dom->html();

        $this->assertSame("<div>Hello World!</div>\n", $html);
    }

    public function testHtmlWithSelectedNodes(): void
    {
        $dom = Dom::fromText(<<<HTML
            <div>
                <p>Hello</p>
            </div>
            <div>
                <p>World!</p>
            </div>
        HTML);
        $p_nodes = $dom->select('//p');

        $this->assertNotNull($p_nodes);

        $html = $p_nodes->html();

        $this->assertSame("<p>Hello</p>\n<p>World!</p>", $html);
    }

    public function testSelect(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>Hello World!</title>
                </head>
                <body>
                    <p>Hello you!</p>
                </body>
            </html>
        HTML);

        $title = $dom->select('//title');

        $this->assertNotNull($title);
        $text = $title->text();
        $this->assertSame('Hello World!', $text);
    }

    public function testSelectIsRelative(): void
    {
        $dom = Dom::fromText(<<<HTML
            <div>
                <p>
                    <a href="#">a link in a paragraph</a>
                </p>

                <span>
                    <a href="#">a link in a span</a>
                </span>
            </div>
        HTML);

        $span = $dom->select('//span');
        $this->assertNotNull($span);
        $link = $span->select('/a');
        $this->assertNotNull($link);
        $text = $link->text();
        $this->assertSame('a link in a span', $text);
    }

    public function testSelectReturnsNullIfInvalid(): void
    {
        $dom = Dom::fromText(<<<HTML
            <title>Hello World!</title>
        HTML);

        $selected = $dom->select('not a xpath query');

        $this->assertNull($selected);
    }

    public function testSelectReturnsNullIfNoMatchingNodes(): void
    {
        $dom = Dom::fromText(<<<HTML
            <title>Hello World!</title>
        HTML);

        $selected = $dom->select('//p');

        $this->assertNull($selected);
    }

    public function testRemove(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <p>Hello World!</p>
                    <div>Hello You!</div>
                </body>
            </html>
        HTML);

        $dom->remove('//div');

        $this->assertSame('Hello World!', $dom->text());
    }

    public function testRemoveRootNode(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                </body>
            </html>
        HTML);

        $dom->remove('/html');

        $this->assertSame('', $dom->text());
    }

    public function testRemoveAltersInitialDomIfSelected(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <p>Hello World!</p>
                    <div>Hello You!</div>
                </body>
            </html>
        HTML);

        $body = $dom->select('//body');
        $this->assertNotNull($body);
        $body->remove('//div');

        $this->assertSame('Hello World!', $body->text());
        $this->assertStringContainsString('Hello World!', $dom->text());
        $this->assertStringNotContainsString('Hello You!', $dom->text());
    }
}
