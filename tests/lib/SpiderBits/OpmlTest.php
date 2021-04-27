<?php

namespace SpiderBits;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    public static $examples_path;

    /**
     * @beforeClass
     */
    public static function setExamplesPath()
    {
        $app_path = \Minz\Configuration::$app_path;
        self::$examples_path = $app_path . '/tests/lib/SpiderBits/examples';
    }

    public function testFromTextWithFreshRss()
    {
        $opml_as_string = file_get_contents(self::$examples_path . '/freshrss.opml.xml');

        $opml = Opml::fromText($opml_as_string);

        $this->assertSame(1, count($opml->outlines));
        $this->assertSame('Blogs', $opml->outlines[0]['text']);
        $this->assertSame(3, count($opml->outlines[0]['outlines']));
        $outlines = $opml->outlines[0]['outlines'];
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

    public function testFromTextWithFeedly()
    {
        $opml_as_string = file_get_contents(self::$examples_path . '/feedly.opml.xml');

        $opml = Opml::fromText($opml_as_string);

        $this->assertSame(1, count($opml->outlines));
        $this->assertSame('Blogs', $opml->outlines[0]['text']);
        $this->assertSame(3, count($opml->outlines[0]['outlines']));
        $outlines = $opml->outlines[0]['outlines'];
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

    public function testFromTextIgnoresNotOutlineElements()
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

    public function testFromTextFailsWithEmptyString()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can’t parse the given string.');

        Opml::fromText('');
    }

    public function testFromTextFailsWithNotXmlString()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can’t parse the given string.');

        Opml::fromText('not xml');
    }

    public function testFromTextFailsWithNotSupportedStandard()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Given string is not OPML.');

        Opml::fromText('<not><a><standard /></a></not>');
    }
}
