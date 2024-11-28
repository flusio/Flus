<?php

namespace SpiderBits;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    public static string $examples_path;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setExamplesPath(): void
    {
        $app_path = \App\Configuration::$app_path;
        self::$examples_path = $app_path . '/tests/lib/SpiderBits/examples';
    }

    public function testFromTextWithFreshRss(): void
    {
        $opml_as_string = file_get_contents(self::$examples_path . '/freshrss.opml.xml');

        $this->assertNotFalse($opml_as_string);

        $opml = Opml::fromText($opml_as_string);

        $this->assertSame(1, count($opml->outlines));
        $this->assertSame('Blogs', $opml->outlines[0]['text']);
        $outlines = $opml->outlines[0]['outlines'];
        $this->assertIsArray($outlines);
        $this->assertSame(3, count($outlines));
        $this->assertSame('Framablog', $outlines[0]['text']);
        $this->assertSame('rss', $outlines[0]['type']);
        $this->assertSame('https://framablog.org/feed/', $outlines[0]['xmlUrl']);
        $this->assertSame('https://framablog.org/', $outlines[0]['htmlUrl']);
        $this->assertSame('La route est longue mais la voie est libre…', $outlines[0]['description']);

        $this->assertSame('carnet de flus', $outlines[1]['text']);
        $this->assertSame('rss', $outlines[1]['type']);
        $this->assertSame('https://flus.fr/carnet/feeds/all.atom.xml', $outlines[1]['xmlUrl']);
        $this->assertSame('https://flus.fr/carnet/', $outlines[1]['htmlUrl']);
        $this->assertSame('', $outlines[1]['description']);

        $this->assertSame('Marien Fressinaud', $outlines[2]['text']);
        $this->assertSame('rss', $outlines[2]['type']);
        $this->assertSame('https://marienfressinaud.fr/feeds/all.atom.xml', $outlines[2]['xmlUrl']);
        $this->assertSame('https://marienfressinaud.fr/', $outlines[2]['htmlUrl']);
        $this->assertSame('', $outlines[2]['description']);
    }

    public function testFromTextWithFeedly(): void
    {
        $opml_as_string = file_get_contents(self::$examples_path . '/feedly.opml.xml');

        $this->assertNotFalse($opml_as_string);

        $opml = Opml::fromText($opml_as_string);

        $this->assertSame(1, count($opml->outlines));
        $this->assertSame('Blogs', $opml->outlines[0]['text']);
        $outlines = $opml->outlines[0]['outlines'];
        $this->assertIsArray($outlines);
        $this->assertSame(3, count($outlines));
        $this->assertSame('Framablog', $outlines[0]['text']);
        $this->assertSame('Framablog', $outlines[0]['title']);
        $this->assertSame('rss', $outlines[0]['type']);
        $this->assertSame('http://www.framablog.org/index.php/feed/atom', $outlines[0]['xmlUrl']);
        $this->assertSame('https://framablog.org', $outlines[0]['htmlUrl']);

        $this->assertSame('carnet de flus', $outlines[1]['text']);
        $this->assertSame('carnet de flus', $outlines[1]['title']);
        $this->assertSame('rss', $outlines[1]['type']);
        $this->assertSame('https://flus.fr/carnet/feeds/all.atom.xml', $outlines[1]['xmlUrl']);
        $this->assertSame('https://flus.fr/carnet/', $outlines[1]['htmlUrl']);

        $this->assertSame('Marien Fressinaud', $outlines[2]['text']);
        $this->assertSame('Marien Fressinaud', $outlines[2]['title']);
        $this->assertSame('rss', $outlines[2]['type']);
        $this->assertSame('https://marienfressinaud.fr/feeds/all.atom.xml', $outlines[2]['xmlUrl']);
        $this->assertSame('https://marienfressinaud.fr/', $outlines[2]['htmlUrl']);
    }

    public function testFromTextHandlesMissingHead(): void
    {
        $string = <<<OPML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0">
                <body>
                    <outline text="carnet de flus" type="rss" xmlUrl="https://flus.fr/carnet/feeds/all.atom.xml" />
                </body>
            </opml>
        OPML;

        $opml = Opml::fromText($string);

        $this->assertSame(1, count($opml->outlines));
        $this->assertSame('carnet de flus', $opml->outlines[0]['text']);
    }

    public function testFromTextIgnoresNotOutlineElements(): void
    {
        $string = <<<OPML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0">
                <head>
                    <title>Some OPML</title>
                </head>
                <body>
                    <inline text="Blogs" />
                </body>
            </opml>
        OPML;

        $opml = Opml::fromText($string);

        $this->assertEmpty($opml->outlines);
    }

    public function testFromTextFailsWithEmptyString(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The string must not be empty.');

        Opml::fromText('');
    }

    public function testFromTextFailsWithNotXmlString(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can’t parse the given string.');

        Opml::fromText('not xml');
    }

    public function testFromTextFailsWithNotSupportedStandard(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Given string is not OPML.');

        Opml::fromText('<not><a><standard /></a></not>');
    }
}
