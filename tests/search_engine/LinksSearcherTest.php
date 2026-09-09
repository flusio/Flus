<?php

namespace App\search_engine;

use App\models;
use tests\factories\UserFactory;
use tests\factories\LinkFactory;

class LinksSearcherTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    public function testGetLinksSearchesByTitle(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        $query = LinksSearcher::buildQuery($title, 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksFindsNothingWithOnlyStopWords(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Le chat noir',
        ]);
        $query = LinksSearcher::buildQuery('le', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(0, count($links));
    }

    public function testGetLinksSearchesWithOr(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'How to bake a cake',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Technology watch',
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Knitting for dummies',
        ]);
        $query = LinksSearcher::buildQuery('cake OR watch', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $link_ids = array_column($links, 'id');
        $this->assertContains($link_1->id, $link_ids);
        $this->assertContains($link_2->id, $link_ids);
    }

    public function testGetLinksGivesPriorityToAndOverOr(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'How to bake a cake',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Technology watch',
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Knitting watch',
        ]);
        // i.e. "cake OR (watch knitting)"
        $query = LinksSearcher::buildQuery('cake OR watch knitting', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $link_ids = array_column($links, 'id');
        $this->assertContains($link_1->id, $link_ids);
        $this->assertContains($link_3->id, $link_ids);
    }

    public function testGetLinksSearchesWithSubQuery(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'How to bake a cake',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Technology watch',
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Knitting watch',
        ]);
        $query = LinksSearcher::buildQuery('(cake OR watch) NOT knitting', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $link_ids = array_column($links, 'id');
        $this->assertContains($link_1->id, $link_ids);
        $this->assertContains($link_2->id, $link_ids);
    }

    public function testGetLinksSearchesByPhrase(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Knitting watch',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Watch and knitting',
        ]);
        $query = LinksSearcher::buildQuery('"knitting watch"', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksCanExcludeByText(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Technology watch',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => 'Knitting watch',
        ]);
        $query = LinksSearcher::buildQuery('watch NOT knitting', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksSearchesByUrl(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $query = LinksSearcher::buildQuery("url: {$url}", 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksSearchesByUrlWithLiteralWildcards(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => 'https://example.com/a_b',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => 'https://example.com/a-b',
        ]);
        // "_" is a LIKE wildcard matching any single char: if it was not
        // escaped, "a_b" would match "a-b" as well.
        $query = LinksSearcher::buildQuery('url:a_b', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksCanExcludeByUrl(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => 'https://example.com/article',
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => 'https://example.org/article',
        ]);
        $query = LinksSearcher::buildQuery('-url:example.com', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByHidden(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $query = LinksSearcher::buildQuery('is:hidden', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksCanExcludeHidden(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $query = LinksSearcher::buildQuery('-is:hidden', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByHasNotes(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_1->addNote(new models\Note($user, 'A note'));
        $query = LinksSearcher::buildQuery('has:notes', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksSearchesByNoNotes(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_1->addNote(new models\Note($user, 'A note'));
        $query = LinksSearcher::buildQuery('no:notes', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByHasTags(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo' => 'foo'],
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => [],
        ]);
        $query = LinksSearcher::buildQuery('has:tags', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksSearchesByNoTags(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo' => 'foo'],
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => [],
        ]);
        $query = LinksSearcher::buildQuery('no:tags', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByDate(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-03-01 00:00:00'),
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-03-31 23:59:59'),
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-04-01 00:00:00'),
        ]);
        $query = LinksSearcher::buildQuery('date:2026-03', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $link_ids = array_column($links, 'id');
        $this->assertContains($link_1->id, $link_ids);
        $this->assertContains($link_2->id, $link_ids);
    }

    public function testGetLinksSearchesByDateInTheTimezoneOfTheApplication(): void
    {
        // The database reads the dates in its own timezone (UTC), so a link
        // created just after the end of a month locally would be included in
        // this month if the timezone was not taken into account. The
        // timezone of the tests being UTC, it has to be changed to make this
        // case reachable.
        $initial_timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        try {
            $user = UserFactory::create();
            $link_1 = LinkFactory::create([
                'user_id' => $user->id,
                'created_at' => new \DateTimeImmutable('2026-03-31 23:59:59'),
            ]);
            $link_2 = LinkFactory::create([
                'user_id' => $user->id,
                'created_at' => new \DateTimeImmutable('2026-04-01 00:00:00'),
            ]);
            $query = LinksSearcher::buildQuery('date:2026-03', 'links');

            $links = LinksSearcher::getLinks($user, $query);

            $this->assertSame(1, count($links));
            $this->assertSame($link_1->id, $links[0]->id);
        } finally {
            date_default_timezone_set($initial_timezone);
        }
    }

    public function testGetLinksSearchesByDateWithOperator(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-03-23 12:00:00'),
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-03-24 00:00:00'),
        ]);
        // i.e. after the end of the day, so the 23th is excluded
        $query = LinksSearcher::buildQuery('date:>2026-03-23', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByDateRange(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2025-12-31 23:59:59'),
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-02-15 12:00:00'),
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-04-01 00:00:00'),
        ]);
        $query = LinksSearcher::buildQuery('date:2026-01..2026-03', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByCurrentDate(): void
    {
        \Minz\Time::freeze(new \DateTimeImmutable('2026-03-23 12:00:00'));
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-03-23 08:00:00'),
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => new \DateTimeImmutable('2026-03-22 08:00:00'),
        ]);
        $query = LinksSearcher::buildQuery('date:today', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksSearchesByDuration(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => 5,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => 15,
        ]);
        $query = LinksSearcher::buildQuery('duration:>=10', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
    }

    public function testGetLinksSearchesByDurationRange(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => 4,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => 5,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => 15,
        ]);
        $link_4 = LinkFactory::create([
            'user_id' => $user->id,
            'reading_time' => 16,
        ]);
        $query = LinksSearcher::buildQuery('duration:5..15', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $link_ids = array_column($links, 'id');
        $this->assertContains($link_2->id, $link_ids);
        $this->assertContains($link_3->id, $link_ids);
    }

    public function testGetLinksSearchesByNotes(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_1->addNote(new models\Note($user, 'A note about knitting'));
        $link_2->addNote(new models\Note($user, 'A note about cakes'));
        $query = LinksSearcher::buildQuery('notes:knitting', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksCanExcludeByNotes(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_1->addNote(new models\Note($user, 'A note about knitting'));
        $link_2->addNote(new models\Note($user, 'A note about cakes'));
        $query = LinksSearcher::buildQuery('-notes:knitting', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $link_ids = array_column($links, 'id');
        $this->assertContains($link_2->id, $link_ids);
        $this->assertContains($link_3->id, $link_ids);
    }

    public function testGetLinksSearchesByNotesPhrase(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_1->addNote(new models\Note($user, 'Knitting watch'));
        $link_2->addNote(new models\Note($user, 'Watch and knitting'));
        $query = LinksSearcher::buildQuery('notes:"knitting watch"', 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testGetLinksSearchesByTag(): void
    {
        /** @var string[] */
        $tags = $this->fake('words', 5);
        /** @var string */
        $tag = $this->fake('randomElement', $tags);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => $tags,
        ]);
        $query = LinksSearcher::buildQuery("#{$tag}", 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksSearchesByTagIgnoringCase(): void
    {
        $tags = ['foo' => 'FOO', 'bar' => 'BAR'];
        $tag = 'Foo';
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => $tags,
        ]);
        $query = LinksSearcher::buildQuery("#{$tag}", 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksCanExcludeByTag(): void
    {
        /** @var string[] */
        $tags = $this->fake('words', 5);
        /** @var string */
        $tag = $this->fake('randomElement', $tags);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => $tags,
        ]);
        $query = LinksSearcher::buildQuery("-#{$tag}", 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(0, count($links));
    }

    public function testGetLinksCanExcludeByMultipleTags(): void
    {
        $tag_to_keep = 'foo';
        $tag_to_exclude1 = 'bar';
        $tag_to_exclude2 = 'baz';
        $user = UserFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo'],
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo', 'bar'],
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo', 'bar', 'baz'],
        ]);
        $query = LinksSearcher::buildQuery("#{$tag_to_keep} -#{$tag_to_exclude1} -#{$tag_to_exclude2}", 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link1->id, $links[0]->id);
    }

    public function testGetLinksSortsByCreatedAt(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $created_at_1 = \Minz\Time::ago(2, 'day');
        $created_at_2 = \Minz\Time::ago(1, 'day');
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
            'created_at' => $created_at_1,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
            'created_at' => $created_at_2,
        ]);
        $query = LinksSearcher::buildQuery($title, 'links');

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
        $this->assertSame($link_1->id, $links[1]->id);
    }

    public function testGetLinksCanLimitResults(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
            'title' => $title,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'title' => $title,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
            'title' => $title,
        ]);
        $query = LinksSearcher::buildQuery($title, 'links');

        $links = LinksSearcher::getLinks($user, $query, pagination: [
            'limit' => 2,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_3->id, $links[0]->id);
        $this->assertSame($link_2->id, $links[1]->id);
    }

    public function testGetLinksCanOffsetResults(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
            'title' => $title,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'title' => $title,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
            'title' => $title,
        ]);
        $query = LinksSearcher::buildQuery($title, 'links');

        $links = LinksSearcher::getLinks($user, $query, pagination: [
            'limit' => 2,
            'offset' => 1,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
        $this->assertSame($link_1->id, $links[1]->id);
    }

    public function testCountLinks(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        $query = LinksSearcher::buildQuery($title, 'links');

        $count = LinksSearcher::countLinks($user, $query);

        $this->assertSame(1, $count);
    }
}
