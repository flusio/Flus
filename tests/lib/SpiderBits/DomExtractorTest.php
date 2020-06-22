<?php

namespace SpiderBits;

class DomExtractorTest extends \PHPUnit\Framework\TestCase
{
    public function testTitle()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>This is title</title>
                </head>
            </html>
        HTML);

        $title = DomExtractor::title($dom);

        $this->assertSame('This is title', $title);
    }

    public function testTitleReturnsEmptyStringIfNotInHtmlHead()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <title>This is title</title>
                </body>
            </html>
        HTML);

        $title = DomExtractor::title($dom);

        $this->assertSame('', $title);
    }

    public function testContentReturnsMainTag()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>This is title</title>
                </head>

                <body>
                    <header>This is header</header>

                    <main>This is main</main>
                </body>
            </html>
        HTML);

        $content = DomExtractor::content($dom);

        $this->assertSame('This is main', $content);
    }

    public function testContentReturnsIdMainIfTagDoesNotExist()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>This is title</title>
                </head>

                <body>
                    <header>This is header</header>

                    <div id="main">This is main</div>
                </body>
            </html>
        HTML);

        $content = DomExtractor::content($dom);

        $this->assertSame('This is main', $content);
    }

    public function testContentReturnsBodyIfNoMainTagOrId()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>This is title</title>
                </head>

                <body>
                    <header>This is header</header>

                    <div>This is main</div>
                </body>
            </html>
        HTML);

        $content = DomExtractor::content($dom);

        $this->assertSame('This is headerThis is main', $content);
    }

    public function testContentReturnsMainTagEvenIfNotInBody()
    {
        // actually, the DOMDocument class automatically repairs the broken HTML
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>This is title</title>
                </head>

                <header>This is header</header>

                <main>This is main</main>
            </html>
        HTML);

        $content = DomExtractor::content($dom);

        $this->assertSame('This is main', $content);
    }

    public function testContentReturnsEmptyIfNoBody()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <title>This is title</title>
                </head>
            </html>
        HTML);

        $content = DomExtractor::content($dom);

        $this->assertSame('', $content);
    }
}
