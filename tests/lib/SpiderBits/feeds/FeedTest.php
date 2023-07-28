<?php

namespace SpiderBits\feeds;

class FeedTest extends \PHPUnit\Framework\TestCase
{
    public static string $examples_path;

    /**
     * @beforeClass
     */
    public static function setExamplesPath(): void
    {
        $app_path = \Minz\Configuration::$app_path;
        self::$examples_path = $app_path . '/tests/lib/SpiderBits/feeds/examples';
    }

    public function testFromTextWithCarnetDeFlus(): void
    {
        $feed_as_string = @file_get_contents(self::$examples_path . '/carnet_de_flus.atom.xml');

        $this->assertNotFalse($feed_as_string);

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
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1576319400, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('97cb4fc2f008714c8e121583bde89b2c50780cec23ffbec33ef4c22383257d38', $feed->hash());
    }

    public function testFromTextWithFramablog(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/framablog.rss.xml');

        $this->assertNotFalse($feed_as_string);

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
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1615790521, $entry->published_at->getTimestamp());
        $this->assertSame(12, count($entry->categories));
        $this->assertSame('Claviers invités', $entry->categories['Claviers invités']);
        $this->assertSame('81f8ed21807a3b40293bb0c9dc14adf9bc5820370c99a768f2e61d176f0b9c67', $feed->hash());
    }

    public function testFromTextWithLaverty(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/lavertygrenoble.rss.xml');

        $this->assertNotFalse($feed_as_string);

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
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1614070752, $entry->published_at->getTimestamp());
        $this->assertSame(5, count($entry->categories));
        $this->assertSame('grenoble', $entry->categories['grenoble']);
        $this->assertSame('d4fb468a697843eacd24d83a3e416583f5467b449abf6b2aa87530373563fd25', $feed->hash());
    }

    public function testFromTextWithVimeoSudWeb(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/vimeo_sud_web.rss.xml');

        $this->assertNotFalse($feed_as_string);

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
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1554818042, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('d5e41e5e21c195e35ad9f952775e67817f4485e5acf92de1ec77547f17339f7f', $feed->hash());
    }

    public function testFromTextWithOatmeal(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/oatmeal.rdf.xml');

        $this->assertNotFalse($feed_as_string);

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
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1618335907, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('38b063cb8e780063ca25310dc24694cf579b6fd74e0b492f2e96db98ee37b148', $feed->hash());
    }

    public function testFromTextWithBlogBasilesimon(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/blog_basilesimon.atom.xml');

        $this->assertNotFalse($feed_as_string);

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
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1620943200, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('1f9c0b7063cfc9eb66d3e75cdc90ebc7e8b171c5f02ad6ab758e70efab0585f3', $feed->hash());
    }

    public function testFromTextWithNotaBene(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/nota-bene.rss.xml');

        $this->assertNotFalse($feed_as_string);

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('rss', $feed->type);
        $this->assertSame('nota-bene.org', $feed->title);
        $this->assertSame(
            'Site personnel de Stéphane Deschamps, expert technique Web et incorrigible bavard, amoureux de la vie.',
            $feed->description
        );
        $this->assertSame('https://nota-bene.org/', $feed->link);
        $this->assertSame('https://nota-bene.org/', $feed->links['alternate']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(3, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('J\'ai pris le large', $entry->title);
        $this->assertSame(
            'https://nota-bene.org/J-ai-pris-le-large',
            $entry->link
        );
        $this->assertSame(
            'https://nota-bene.org/J-ai-pris-le-large',
            $entry->links['alternate']
        );
        $this->assertSame('https://nota-bene.org/J-ai-pris-le-large', $entry->id);
        $this->assertSame('', trim($entry->content));
        $this->assertSame('html', $entry->content_type);
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1663407060, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('59232c6b8b0d545052a613f5e38a96d46db53e3be69733024ff7684cd6109b42', $feed->hash());
    }

    public function testFromTextWithCommitstrip(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/commitstrip.rss.xml');

        $this->assertNotFalse($feed_as_string);

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('rss', $feed->type);
        $this->assertSame('CommitStrip', $feed->title);
        $this->assertSame('The blog relating the daily life of web agency developers', $feed->description);
        $this->assertSame('https://www.commitstrip.com', $feed->link);
        $this->assertSame('https://www.commitstrip.com', $feed->links['alternate']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(3, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('C&rsquo;est l&rsquo;histoire d&rsquo;une conf-call Teams', $entry->title);
        $this->assertSame(
            'https://www.commitstrip.com/2022/09/13/once-upon-a-teams-meeting/',
            $entry->link
        );
        $this->assertSame(
            'https://www.commitstrip.com/2022/09/13/once-upon-a-teams-meeting/',
            $entry->links['alternate']
        );
        $this->assertSame('https://www.commitstrip.com/2022/09/13/once-upon-a-teams-meeting/', $entry->id);
        $this->assertSame('', trim($entry->content));
        $this->assertSame('html', $entry->content_type);
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1663087290, $entry->published_at->getTimestamp());
        $this->assertSame(1, count($entry->categories));
        $this->assertSame('9e5d2601871593c916b93e205cf4c80ebe11aaa08a55a1b220f0f6b634f35a8e', $feed->hash());
    }

    public function testFromTextWithJsonFeed(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/jsonfeed.json');

        $this->assertNotFalse($feed_as_string);

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('json', $feed->type);
        $this->assertSame('JSON Feed', $feed->title);
        $this->assertSame('', $feed->description);
        $this->assertSame('https://www.jsonfeed.org/', $feed->link);
        $this->assertSame('https://www.jsonfeed.org/', $feed->links['alternate']);
        $this->assertSame('https://www.jsonfeed.org/feed.json', $feed->links['self']);
        $this->assertSame(0, count($feed->categories));
        $this->assertSame(2, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame('JSON Feed version 1.1', $entry->title);
        $this->assertSame(
            'https://www.jsonfeed.org/2020/08/07/json-feed-version.html',
            $entry->link
        );
        $this->assertSame(
            'https://www.jsonfeed.org/2020/08/07/json-feed-version.html',
            $entry->links['alternate']
        );
        $this->assertSame('http://jsonfeed.micro.blog/2020/08/07/json-feed-version.html', $entry->id);
        $this->assertStringContainsString('We&rsquo;ve updated the spec', $entry->content);
        $this->assertSame('html', $entry->content_type);
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1596818676, $entry->published_at->getTimestamp());
        $this->assertSame(0, count($entry->categories));
        $this->assertSame('04a990debeee9eb515c9bb5fdd5b732f692b723a84776842628cb93c39e7d271', $feed->hash());
    }

    public function testFromTextWithDatesWithMilliseconds(): void
    {
        $feed_as_string = file_get_contents(self::$examples_path . '/dates-with-milliseconds.atom.xml');

        $this->assertNotFalse($feed_as_string);

        $feed = Feed::fromText($feed_as_string);

        $this->assertSame('atom', $feed->type);
        $this->assertSame('A feed with milliseconds', $feed->title);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertNotNull($entry->published_at);
        $this->assertSame(1633339200, $entry->published_at->getTimestamp());
    }

    public function testFromTextWithEmptyRss(): void
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

    public function testFromTextFailsWithEmptyString(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The string must not be empty.');

        Feed::fromText('');
    }

    public function testFromTextFailsWithNotXmlString(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can’t parse the given string.');

        Feed::fromText('not xml');
    }

    public function testFromTextFailsWithNotSupportedStandard(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Given string is not a supported standard.');

        Feed::fromText('<not><a><standard /></a></not>');
    }

    public function testIsFeedWithRssReturnsTrue(): void
    {
        $result = Feed::isFeed('<rss></rss>');

        $this->assertTrue($result);
    }

    public function testIsFeedWithAtomReturnsTrue(): void
    {
        $result = Feed::isFeed('<feed></feed>');

        $this->assertTrue($result);
    }

    public function testIsFeedWithEmptyStringReturnsFalse(): void
    {
        $result = Feed::isFeed('');

        $this->assertFalse($result);
    }

    public function testIsFeedWithNotXmlStringReturnsFalse(): void
    {
        $result = Feed::isFeed('not xml');

        $this->assertFalse($result);
    }

    public function testIsFeedWithNotSupportedStandardReturnsFalse(): void
    {
        $result = Feed::isFeed('<not><a><standard /></a></not>');

        $this->assertFalse($result);
    }

    /**
     * @dataProvider validContentType
     */
    public function testIsFeedContentTypeWithValidContentTypeReturnsTrue(string $content_type): void
    {
        $result = Feed::isFeedContentType($content_type);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider invalidContentType
     */
    public function testIsFeedContentTypeWithInvalidContentTypeReturnsTrue(string $content_type): void
    {
        $result = Feed::isFeedContentType($content_type);

        $this->assertFalse($result);
    }

    /**
     * @return array<array{string}>
     */
    public function validContentType(): array
    {
        return [
            ['application/atom+xml'],
            ['application/rss+xml'],
            ['application/x-rss+xml'],
            ['application/feed+json'],
            ['application/json'],
            ['application/rdf+xml'],
            ['application/xml'],
            ['text/rss+xml'],
            ['text/xml'],
            ['text/plain'],
        ];
    }

    /**
     * @return array<array{string}>
     */
    public function invalidContentType(): array
    {
        return [
            [''],
            ['some text'],
            ['text/html'],
        ];
    }
}
