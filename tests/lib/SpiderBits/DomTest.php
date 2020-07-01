<?php

namespace SpiderBits;

class DomTest extends \PHPUnit\Framework\TestCase
{
    public function testText()
    {
        $dom = Dom::fromText(<<<HTML
            <title>Hello World!</title>
        HTML);

        $text = $dom->text();

        $this->assertSame('Hello World!', $text);
    }

    public function testTextWithMixOfHtmlEntitiesAndUtf8()
    {
        $dom = Dom::fromText(<<<HTML
            <title>Site d&#039;information français</title>
        HTML);

        $text = $dom->text();

        $this->assertSame("Site d'information français", $text);
    }

    public function testSelect()
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

        $text = $title->text();
        $this->assertSame('Hello World!', $text);
    }

    public function testSelectIsRelative()
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
        $link = $span->select('/a');

        $text = $link->text();
        $this->assertSame('a link in a span', $text);
    }

    public function testSelectReturnsNullIfInvalid()
    {
        $dom = Dom::fromText(<<<HTML
            <title>Hello World!</title>
        HTML);

        $selected = $dom->select('not a xpath query');

        $this->assertNull($selected);
    }

    public function testSelectReturnsNullIfNoMatchingNodes()
    {
        $dom = Dom::fromText(<<<HTML
            <title>Hello World!</title>
        HTML);

        $selected = $dom->select('//p');

        $this->assertNull($selected);
    }

    public function testRemove()
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

    public function testRemoveRootNode()
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

    public function testRemoveDoesNotAlterInitialDomIfSelected()
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
        $body->remove('//div');

        $this->assertStringContainsString('Hello World!', $dom->text());
        $this->assertStringContainsString('Hello You!', $dom->text());
        $this->assertSame('Hello World!', $body->text());
    }
}
