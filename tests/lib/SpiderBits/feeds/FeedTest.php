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

        $this->assertSame('atom', $feed->type);
        $this->assertSame('carnet de flus', $feed->title);
        $this->assertSame('Le blog de Flus', $feed->description);
        $this->assertSame('https://flus.fr/carnet/', $feed->link);
        $this->assertSame('https://flus.fr/carnet/', $feed->links['alternate']);
        $this->assertSame('https://flus.fr/carnet/feeds/all.atom.xml', $feed->links['self']);
        $this->assertSame('https://websub.flus.io/', $feed->links['hub']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(10, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame(
            'Récap #1 : lancement de mon activité, ouverture du service et découverte de WebSub',
            $entry->title
        );
        $this->assertSame('urn:uuid:7e0b078e-c996-5c76-a529-c420d03672d2', $entry->id);
        $this->assertSame('https://flus.fr/carnet/recap-1.html', $entry->link);
        $this->assertSame('https://flus.fr/carnet/recap-1.html', $entry->links['alternate']);
        $this->assertStringContainsString(
            '<p>J’ai démarré mon activité de micro-entrepreneur début novembre',
            $entry->content
        );
        $this->assertSame('html', $entry->content_type);
        $this->assertSame(1576319400, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('00f9aa3dd1cedf999f3480b60d57c4662a8539ce05f245ca8698e543fc59ec93', $feed->hash());
    }

    public function testFromTextWithFramablog()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/framablog.rss.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('rss', $feed->type);
        $this->assertSame('Framablog', $feed->title);
        $this->assertSame('La route est longue mais la voie est libre…', $feed->description);
        $this->assertSame('https://framablog.org', $feed->link);
        $this->assertSame('https://framablog.org', $feed->links['alternate']);
        $this->assertSame('https://framablog.org/feed/', $feed->links['self']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(10, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('Khrys’presso du lundi 15 mars 2021', $entry->title);
        $this->assertStringContainsString(
            '<p>Tous les liens listés ci-dessous sont <em>a priori</em> accessibles librement.',
            $entry->content
        );
        $this->assertSame('https://framablog.org/?p=24275', $entry->id);
        $this->assertSame('https://framablog.org/2021/03/15/khryspresso-du-lundi-15-mars-2021/', $entry->link);
        $this->assertSame(
            'https://framablog.org/2021/03/15/khryspresso-du-lundi-15-mars-2021/',
            $entry->links['alternate']
        );
        $this->assertSame(
            'https://framablog.org/2021/03/15/khryspresso-du-lundi-15-mars-2021/#respond',
            $entry->links['replies']
        );
        $this->assertSame(1615790521, $entry->published_at->getTimestamp());
        $this->assertSame(12, count($entry->categories));
        $this->assertSame('Claviers invités', $entry->categories['Claviers invités']);
        $this->assertSame('7c03c7e75fcee0c8afe15e71e6a5f8bd1dd091275c4b527c167dfa7c13b9fdb5', $feed->hash());
    }

    public function testFromTextWithLaverty()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/lavertygrenoble.rss.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('rss', $feed->type);
        $this->assertSame("Stories by L'avertY on Medium", $feed->title);
        $this->assertSame("Stories by L'avertY on Medium", $feed->description);
        $this->assertSame('https://medium.com/@lavertygrenoble?source=rss-644186d8e612------2', $feed->link);
        $this->assertSame(
            'https://medium.com/@lavertygrenoble?source=rss-644186d8e612------2',
            $feed->links['alternate']
        );
        $this->assertSame('https://medium.com/feed/@lavertygrenoble', $feed->links['self']);
        $this->assertSame('http://medium.superfeedr.com', $feed->links['hub']);
        $this->assertSame(10, count($feed->entries));
        $this->assertSame(0, count($feed->categories));
        $entry = $feed->entries[0];
        $this->assertSame('« L’idée est d’éviter des montagnes d’emballages jetables »', $entry->title);
        $this->assertSame('https://medium.com/p/82370c70e84', $entry->id);
        $this->assertSame(
            'https://medium.com/@lavertygrenoble/lid%C3%A9e-est-d-%C3%A9viter-des-montagnes-d-emballages-jetables-82370c70e84?source=rss-644186d8e612------2', // phpcs:ignore Generic.Files.LineLength.TooLong
            $entry->link
        );
        $this->assertSame(
            'https://medium.com/@lavertygrenoble/lid%C3%A9e-est-d-%C3%A9viter-des-montagnes-d-emballages-jetables-82370c70e84?source=rss-644186d8e612------2', // phpcs:ignore Generic.Files.LineLength.TooLong
            $entry->links['alternate']
        );
        $this->assertStringContainsString(
            '<h3>📣 « L’idée est d’éviter des montagnes d’emballages jetables »</h3>',
            $entry->content
        );
        $this->assertSame('html', $entry->content_type);
        $this->assertSame(1614070752, $entry->published_at->getTimestamp());
        $this->assertSame(5, count($entry->categories));
        $this->assertSame('grenoble', $entry->categories['grenoble']);
        $this->assertSame('58620704546afb2128dadb6a9a7a3c9f0e400cf95c8d48be8fd86ab45da7b165', $feed->hash());
    }

    public function testFromTextWithVimeoSudWeb()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/vimeo_sud_web.rss.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('rss', $feed->type);
        $this->assertSame('', $feed->title);
        $this->assertSame('', $feed->description);
        $this->assertSame('https://vimeo.com', $feed->link);
        $this->assertSame('https://vimeo.com', $feed->links['alternate']);
        $this->assertSame('https://vimeo.com', $feed->links['self']);
        $this->assertSame('https://pubsubhubbub.appspot.com/', $feed->links['hub']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(25, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('', $entry->title);
        $this->assertSame('tag:vimeo,2019-04-09:clip329347268', $entry->id);
        $this->assertSame('https://vimeo.com/329347268', $entry->link);
        $this->assertSame('', $entry->content);
        $this->assertSame('html', $entry->content_type);
        $this->assertSame(1554818042, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('ec42d965b28efa4710e421be54d9ccabca4160814a579961ad678228ad8aef36', $feed->hash());
    }

    public function testFromTextWithOatmeal()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/oatmeal.rdf.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('rdf', $feed->type);
        $this->assertSame('The Oatmeal - Comics, Quizzes, & Stories', $feed->title);
        $this->assertSame(
            'The oatmeal tastes better than stale skittles found under the couch cushions',
            $feed->description
        );
        $this->assertSame('http://theoatmeal.com/', $feed->link);
        $this->assertSame('http://theoatmeal.com/', $feed->links['alternate']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(9, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('A Little Wordy', $entry->title);
        $this->assertSame('http://expktns.co/OatmealALW', $entry->link);
        $this->assertSame('http://expktns.co/OatmealALW', $entry->id);
        $this->assertStringContainsString(
            '<a href="http://expktns.co/OatmealALW">View on my website</a>',
            $entry->content
        );
        $this->assertSame('html', $entry->content_type);
        $this->assertSame(1618335907, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('4d417e6c0d5101aa223aab83504b267f061abbc43ad5acbd5d5fb65d0b91aefc', $feed->hash());
    }

    public function testFromTextWithBlogBasilesimon()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/blog_basilesimon.atom.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('atom', $feed->type);
        $this->assertSame('Basile Simon (blog)', $feed->title);
        $this->assertSame('', $feed->description);
        $this->assertSame('https://blog.basilesimon.fr/', $feed->link);
        $this->assertSame('https://blog.basilesimon.fr/', $feed->links['alternate']);
        $this->assertSame('https://blog.basilesimon.fr/atom.xml', $feed->links['self']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(10, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('Good busy and Toolkit progress - weeknotes #33', $entry->title);
        $this->assertSame(
            'https://blog.basilesimon.fr/2021/05/14/weeknotes-33/?utm_medium=rss',
            $entry->link
        );
        $this->assertSame(
            'https://blog.basilesimon.fr/2021/05/14/weeknotes-33/?utm_medium=rss',
            $entry->links['alternate']
        );
        $this->assertSame('https://blog.basilesimon.fr/2021/05/14/weeknotes-33', $entry->id);
        $this->assertSame('', $entry->content);
        $this->assertSame('html', $entry->content_type);
        $this->assertSame(1620943200, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('a92e7df76dcdabe9f70d7eaf2798b2a6190608d1b1a37f41a12897c0e00d2dc0', $feed->hash());
    }

    public function testFromTextWithDatesWithMilliseconds()
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/dates-with-milliseconds.atom.xml');

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('atom', $feed->type);
        $this->assertSame('A feed with milliseconds', $feed->title);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame(1633339200, $entry->published_at->getTimestamp());
    }

    public function testFromTextWithEmptyRss()
    {
        $feed = Feed::fromText('<rss></rss>');

        $this->assertSame('', $feed->title);
        $this->assertSame('', $feed->description);
        $this->assertSame('', $feed->link);
        $this->assertSame(0, count($feed->links));
        $this->assertSame(0, count($feed->entries));
        $this->assertSame(0, count($feed->categories));
        $this->assertSame('4a022608d595e9000d1f1be22a0a6a0763ad853d2417b1c8ea0ea12bd047bdcd', $feed->hash());
    }

    public function testFromTextFailsWithEmptyString()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The string must not be empty.');

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

    public function testIsFeedWithRssReturnsTrue()
    {
        $result = Feed::isFeed('<rss></rss>');

        $this->assertTrue($result);
    }

    public function testIsFeedWithAtomReturnsTrue()
    {
        $result = Feed::isFeed('<feed></feed>');

        $this->assertTrue($result);
    }

    public function testIsFeedWithEmptyStringReturnsFalse()
    {
        $result = Feed::isFeed('');

        $this->assertFalse($result);
    }

    public function testIsFeedWithNotXmlStringReturnsFalse()
    {
        $result = Feed::isFeed('not xml');

        $this->assertFalse($result);
    }

    public function testIsFeedWithNotSupportedStandardReturnsFalse()
    {
        $result = Feed::isFeed('<not><a><standard /></a></not>');

        $this->assertFalse($result);
    }

    /**
     * @dataProvider validContentType
     */
    public function testIsFeedContentTypeWithValidContentTypeReturnsTrue($content_type)
    {
        $result = Feed::isFeedContentType($content_type);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider invalidContentType
     */
    public function testIsFeedContentTypeWithInvalidContentTypeReturnsTrue($content_type)
    {
        $result = Feed::isFeedContentType($content_type);

        $this->assertFalse($result);
    }

    public function validContentType()
    {
        return [
            ['application/atom+xml'],
            ['application/rss+xml'],
            ['application/x-rss+xml'],
            ['application/rdf+xml'],
            ['application/xml'],
            ['text/rss+xml'],
            ['text/xml'],
            ['text/plain'],
        ];
    }

    public function invalidContentType()
    {
        return [
            [''],
            ['some text'],
            ['text/html'],
        ];
    }
}
