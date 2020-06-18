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
}
