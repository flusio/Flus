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

    public function testTitleTakesFirstTitleIfNotInHtmlHead()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <title>This is title 1</title>
                    <title>This is title 2</title>
                </body>
            </html>
        HTML);

        $title = DomExtractor::title($dom);

        $this->assertSame('This is title 1', $title);
    }

    public function testTitleDoesNotConsiderSvgTitle()
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <svg viewBox="0 0 20 10" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="5" cy="5" r="4">
                            <title>I'm a circle</title>
                        </circle>
                    </svg>
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

    public function testContentStripsScripts()
    {
        $dom = Dom::fromText(<<<HTML
            <main>
                This is main

                <script>
                    console.log('Hello!');
                </script>
            </main>
        HTML);

        $content = DomExtractor::content($dom);

        $this->assertSame('This is main', $content);
    }
}
