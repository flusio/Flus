<?php

namespace SpiderBits\feeds;

class FeedTest extends \PHPUnit\Framework\TestCase
{
    public static $examples_path;

    /**
     * @beforeClass
     */
    public static function setExamplesPath()
    {
        $app_path = \Minz\Configuration::$app_path;
        self::$examples_path = $app_path . '/tests/lib/SpiderBits/feeds/examples';
    }

    public function testFromTextWithCarnetDeFlus()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/carnet_de_flus.atom.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('carnet de flus', $feed->title);
        $this->assertSame('Le blog de Flus', $feed->description);
        $this->assertSame('https://flus.fr/carnet/', $feed->link);
        $this->assertSame(10, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame(
            'Récap #1 : lancement de mon activité, ouverture du service et découverte de WebSub',
            $entry->title
        );
        $this->assertSame('urn:uuid:7e0b078e-c996-5c76-a529-c420d03672d2', $entry->id);
        $this->assertSame('https://flus.fr/carnet/recap-1.html', $entry->link);
        $this->assertSame(1576319400, $entry->published_at->getTimestamp());
    }

    public function testFromTextWithFramablog()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/framablog.rss.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('Framablog', $feed->title);
        $this->assertSame('La route est longue mais la voie est libre…', $feed->description);
        $this->assertSame('https://framablog.org', $feed->link);
        $this->assertSame(10, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('Khrys’presso du lundi 15 mars 2021', $entry->title);
        $this->assertSame('https://framablog.org/?p=24275', $entry->id);
        $this->assertSame('https://framablog.org/2021/03/15/khryspresso-du-lundi-15-mars-2021/', $entry->link);
        $this->assertSame(1615790521, $entry->published_at->getTimestamp());
    }

    public function testFromTextWithLaverty()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/lavertygrenoble.rss.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame("Stories by L'avertY on Medium", $feed->title);
        $this->assertSame("Stories by L'avertY on Medium", $feed->description);
        $this->assertSame('https://medium.com/@lavertygrenoble?source=rss-644186d8e612------2', $feed->link);
        $this->assertSame(10, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('« L’idée est d’éviter des montagnes d’emballages jetables »', $entry->title);
        $this->assertSame('https://medium.com/p/82370c70e84', $entry->id);
        $this->assertSame(
            'https://medium.com/@lavertygrenoble/lid%C3%A9e-est-d-%C3%A9viter-des-montagnes-d-emballages-jetables-82370c70e84?source=rss-644186d8e612------2', // phpcs:ignore Generic.Files.LineLength.TooLong
            $entry->link
        );
        $this->assertSame(1614070752, $entry->published_at->getTimestamp());
    }

    public function testFromTextWithVimeoSudWeb()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/vimeo_sud_web.rss.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('', $feed->title);
        $this->assertSame('', $feed->description);
        $this->assertSame('https://vimeo.com', $feed->link);
        $this->assertSame(25, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('', $entry->title);
        $this->assertSame('tag:vimeo,2019-04-09:clip329347268', $entry->id);
        $this->assertSame('https://vimeo.com/329347268', $entry->link);
        $this->assertSame(1554818042, $entry->published_at->getTimestamp());
    }

    public function testFromTextWithEmptyRss()
    {
        $feed = Feed::fromText('<rss></rss>');

        $this->assertSame('', $feed->title);
        $this->assertSame('', $feed->description);
        $this->assertSame('', $feed->link);
        $this->assertSame(0, count($feed->entries));
    }

    public function testFromTextFailsWithEmptyString()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can’t parse the given string.');

        Feed::fromText('');
    }

    public function testFromTextFailsWithNotXmlString()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can’t parse the given string.');

        Feed::fromText('not xml');
    }

    public function testFromTextFailsWithNotSupportedStandard()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Given string is not a supported standard.');

        Feed::fromText('<not><a><standard /></a></not>');
    }
}
