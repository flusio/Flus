<?php

namespace SpiderBits;

class DomExtractorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;

    public function testTitle(): void
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

    public function testTitleTakesFirstTitleIfNotInHtmlHead(): void
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

    public function testTitleTakesOgTitle(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <meta property="og:title" content="This is title 1" />
                    <meta property="og:title" content="This is title 2" />
                </head>
            </html>
        HTML);

        $title = DomExtractor::title($dom);

        $this->assertSame('This is title 1', $title);
    }

    public function testTitleTakesTwitterTitle(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <meta name="twitter:title" content="This is title 1" />
                    <meta name="twitter:title" content="This is title 2" />
                </head>
            </html>
        HTML);

        $title = DomExtractor::title($dom);

        $this->assertSame('This is title 1', $title);
    }

    public function testTitleDoesNotConsiderSvgTitle(): void
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

    public function testDescriptionTakesFirstDescriptionIfNotInHtmlHead(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <meta name="description" content="This is description 1" />
                    <meta name="description" content="This is description 2" />
                </body>
            </html>
        HTML);

        $description = DomExtractor::description($dom);

        $this->assertSame('This is description 1', $description);
    }

    public function testDescriptionTakesOgDescription(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <meta property="og:description" content="This is description 1" />
                    <meta property="og:description" content="This is description 2" />
                </head>
            </html>
        HTML);

        $description = DomExtractor::description($dom);

        $this->assertSame('This is description 1', $description);
    }

    public function testDescriptionTakesTwitterDescription(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <meta name="twitter:description" content="This is description 1" />
                    <meta name="twitter:description" content="This is description 2" />
                </head>
            </html>
        HTML);

        $description = DomExtractor::description($dom);

        $this->assertSame('This is description 1', $description);
    }

    public function testIllustrationTakesOgImage(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <meta property="og:image" content="https://domain.com/image.jpg" />
                </head>
            </html>
        HTML);

        $illustration = DomExtractor::illustration($dom);

        $this->assertSame('https://domain.com/image.jpg', $illustration);
    }

    public function testIllustrationTakesTwitterImage(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <meta name="twitter:image" content="https://domain.com/image.jpg" />
                </head>
            </html>
        HTML);

        $illustration = DomExtractor::illustration($dom);

        $this->assertSame('https://domain.com/image.jpg', $illustration);
    }

    public function testContentReturnsMainTag(): void
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

    public function testContentReturnsIdMainIfTagDoesNotExist(): void
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

    public function testContentReturnsBodyIfNoMainTagOrId(): void
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

        $this->assertStringContainsString('This is header', $content);
        $this->assertStringContainsString('This is main', $content);
    }

    public function testContentReturnsMainTagEvenIfNotInBody(): void
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

    public function testContentReturnsEmptyIfNoBody(): void
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

    public function testContentStripsScripts(): void
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

    public function testDuration(): void
    {
        /** @var string */
        $content = $this->fake('words', 400, true);
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <main>
                        {$content}
                    </main>
                </body>
            </html>
        HTML);

        $duration = DomExtractor::duration($dom);

        $this->assertSame(2, $duration);
    }

    public function testDurationWithItempropAttribute(): void
    {
        /** @var string */
        $content = $this->fake('words', 400, true);
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <meta itemprop="duration" content="PT41M35S" />

                    <main>
                        {$content}
                    </main>
                </body>
            </html>
        HTML);

        $duration = DomExtractor::duration($dom);

        $this->assertSame(42, $duration);
    }

    public function testDurationWithInvalidItempropAttribute(): void
    {
        /** @var string */
        $content = $this->fake('words', 400, true);
        $dom = Dom::fromText(<<<HTML
            <html>
                <body>
                    <meta itemprop="duration" content="NotIso8601" />

                    <main>
                        {$content}
                    </main>
                </body>
            </html>
        HTML);

        $duration = DomExtractor::duration($dom);

        $this->assertSame(2, $duration);
    }

    public function testFeeds(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                    <link rel="alternate" type="application/rss+xml" title="RSS Feed" href="/rss" />
                    <link rel="alternate" type="application/atom+xml" title="Atom Feed" href="/atom" />
                </head>
            </html>
        HTML);

        $feeds = DomExtractor::feeds($dom);

        $this->assertSame(2, count($feeds));
        $this->assertSame('/rss', $feeds[0]);
        $this->assertSame('/atom', $feeds[1]);
    }

    public function testFeedsWithNoLinks(): void
    {
        $dom = Dom::fromText(<<<HTML
            <html>
                <head>
                </head>
            </html>
        HTML);

        $feeds = DomExtractor::feeds($dom);

        $this->assertSame(0, count($feeds));
    }
}
