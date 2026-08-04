<?php

namespace App\models;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class StreamViewTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setupRouter(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    public function testBuildFromRequestSetsOptionsFromRequestParameters(): void
    {
        $date = $this->fakeDateInPeriod()->format('Y-m-d');
        /** @var int */
        $days = $this->fake('numberBetween', 1, 7);
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date,
            'days' => $days,
        ]);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $this->assertSame($date, $stream_view->at->format('Y-m-d'));
        $this->assertSame($days, $stream_view->days);
    }

    public function testBuildFromRequestHasDefaultValues(): void
    {
        $now = \Minz\Time::now();
        $request = new \Minz\Request('GET', '/stream', []);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $this->assertSame($now->format('Y-m-d'), $stream_view->at->format('Y-m-d'));
        $this->assertSame(1, $stream_view->days);
        $this->assertSame('', $stream_view->query);
        $this->assertNull($stream_view->search_query);
    }

    public function testBuildFromRequestSetsQueryFromRequestParameters(): void
    {
        $request = new \Minz\Request('GET', '/stream', [
            'q' => '  word  ',
        ]);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $this->assertSame('word', $stream_view->query);
        $this->assertNotNull($stream_view->search_query);
    }

    public function testBuildFromRequestIgnoresMalformedQuery(): void
    {
        $request = new \Minz\Request('GET', '/stream', [
            'q' => '""',
        ]);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        // The query is kept as it is typed, but it is not applied to the links.
        $this->assertSame('""', $stream_view->query);
        $this->assertNull($stream_view->search_query);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('atOutOfPeriodProvider')]
    public function testBuildFromRequestLimitsAtToThePeriod(string $at, string $expected_at): void
    {
        $request = new \Minz\Request('GET', '/stream', [
            'at' => \Minz\Time::relative($at)->format('Y-m-d'),
        ]);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $expected_date = \Minz\Time::relative($expected_at);
        $this->assertSame($expected_date->format('Y-m-d'), $stream_view->at->format('Y-m-d'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('daysOutOfLimitsProvider')]
    public function testBuildFromRequestLimitsDays(int $days, int $expected_days): void
    {
        $request = new \Minz\Request('GET', '/stream', [
            'days' => $days,
        ]);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $this->assertSame($expected_days, $stream_view->days);
    }

    public function testIsAt(): void
    {
        $date_1 = $this->fakeDateInPeriod();
        $date_2 = $date_1->modify('-1 day');
        $stream = StreamFactory::create();
        $stream_view = new StreamView($stream, null, at: $date_1);

        $is_at_date_1 = $stream_view->isAt($date_1);
        $is_at_date_2 = $stream_view->isAt($date_2);

        $this->assertTrue($is_at_date_1);
        $this->assertFalse($is_at_date_2);
    }

    public function testIsInRange(): void
    {
        $date = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $stream_view = new StreamView($stream, null, at: $date, days: 2);

        $is_in_range_at = $stream_view->isInRange($date);
        $is_in_range_day_before = $stream_view->isInRange($date->modify('-1 day'));
        $is_in_range_two_days_before = $stream_view->isInRange($date->modify('-2 days'));
        $is_in_range_day_after = $stream_view->isInRange($date->modify('+1 day'));

        $this->assertTrue($is_in_range_at);
        $this->assertTrue($is_in_range_day_before);
        $this->assertFalse($is_in_range_two_days_before);
        $this->assertFalse($is_in_range_day_after);
    }

    public function testIsSourceSelected(): void
    {
        $date = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = new StreamView($stream, null, at: $date, source: $source_1);

        $is_source_1_selected = $stream_view->isSourceSelected($source_1);
        $is_source_2_selected = $stream_view->isSourceSelected($source_2);

        $this->assertTrue($is_source_1_selected);
        $this->assertFalse($is_source_2_selected);
    }

    public function testIsSourceSelectedIfSourceHasNoLinkOverThePeriod(): void
    {
        $date = $this->fakeDateInPeriod();
        $other_date = $date->modify('-1 day');
        $stream = StreamFactory::create();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $other_date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, null, at: $date, source: $source);

        $is_source_selected = $stream_view->isSourceSelected($source);

        $this->assertFalse($is_source_selected);
        $this->assertNull($stream_view->source);
    }

    public function testIsSourceSelectedIfSourceHasNoLinkMatchingTheStatus(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link);
        $stream_view = new StreamView($stream, $user, at: $date, source: $source, status: 'unread');

        $is_source_selected = $stream_view->isSourceSelected($source);

        $this->assertFalse($is_source_selected);
        $this->assertNull($stream_view->source);
    }

    public function testIsSourceSelectedIfSourceHasNoLinkMatchingTheQuery(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'title' => 'Another subject',
            'url' => 'https://example.com/other',
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, source: $source, query: 'foos');

        $is_source_selected = $stream_view->isSourceSelected($source);

        $this->assertFalse($is_source_selected);
        $this->assertNull($stream_view->source);
    }

    public function testIsStatusSelected(): void
    {
        $date = $this->fakeDateInPeriod();
        $status_all = 'all';
        $status_unread = 'unread';
        $stream = StreamFactory::create();
        $stream_view = new StreamView($stream, null, at: $date, status: $status_all);

        $is_status_all = $stream_view->isStatusSelected($status_all);
        $is_status_unread = $stream_view->isStatusSelected($status_unread);

        $this->assertTrue($is_status_all);
        $this->assertFalse($is_status_unread);
    }

    public function testPeriod(): void
    {
        $date = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $stream_view = new StreamView($stream, null, at: $date);

        $period = $stream_view->period();

        $expected_first_day = \Minz\Time::now();
        $expected_last_day = \Minz\Time::ago(29, 'days');
        $this->assertSame(30, count($period));
        $this->assertSame($expected_first_day->format('Y-m-d'), $period[0]->format('Y-m-d'));
        $this->assertSame($expected_last_day->format('Y-m-d'), $period[29]->format('Y-m-d'));
    }

    public function testLinksTimelineListsLinksOfSelectedDay(): void
    {
        $date_1 = $this->fakeDateInPeriod();
        $date_2 = $date_1->modify('-1 day');
        $date_3 = $date_1->modify('-2 days');
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date_1->format('Y-m-d'),
            'days' => 1,
        ]);
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1], at: $date_1);
        $source->addLinks([$link_2], at: $date_2);
        $source->addLinks([$link_3], at: $date_3);
        $stream->addSource($source);
        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineListsLinksOfSelectedDayPlusDays(): void
    {
        $date_1 = $this->fakeDateInPeriod();
        $date_2 = $date_1->modify('-1 day');
        $date_3 = $date_1->modify('-2 days');
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date_1->format('Y-m-d'),
            'days' => 2,
        ]);
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1], at: $date_1);
        $source->addLinks([$link_2], at: $date_2);
        $source->addLinks([$link_3], at: $date_3);
        $stream->addSource($source);
        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(2, count($date_groups));

        $date_group_1 = array_shift($date_groups);
        $this->assertNotNull($date_group_1);
        $source_groups_1 = $date_group_1->sourceGroups();
        $this->assertSame(1, count($source_groups_1));
        $source_group_1 = $source_groups_1[0];
        $this->assertSame($source->id, $source_group_1->source->id);
        $this->assertSame(1, count($source_group_1->links));
        $this->assertSame($link_1->id, $source_group_1->links[0]->id);

        $date_group_2 = array_shift($date_groups);
        $this->assertNotNull($date_group_2);
        $source_groups_2 = $date_group_2->sourceGroups();
        $this->assertSame(1, count($source_groups_2));
        $source_group_2 = $source_groups_2[0];
        $this->assertSame($source->id, $source_group_2->source->id);
        $this->assertSame(1, count($source_group_2->links));
        $this->assertSame($link_2->id, $source_group_2->links[0]->id);
    }

    public function testLinksTimelineCanListLinksBySource(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = new StreamView($stream, $user, at: $date, source: $source_1);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source_1->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineCanListLinksByReadStatus(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2, $link_3], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link_1);
        $user->markAsReadLater($link_2);
        $stream_view = new StreamView($stream, $user, at: $date, status: 'read');

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineCanListLinksByReadLaterStatus(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2, $link_3], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link_1);
        $user->markAsReadLater($link_2);
        $stream_view = new StreamView($stream, $user, at: $date, status: 'read-later');

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_2->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineCanListLinksByUnreadStatus(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2, $link_3], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link_1);
        $user->markAsReadLater($link_2);
        $stream_view = new StreamView($stream, $user, at: $date, status: 'unread');

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_3->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineCanListLinksByQuery(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'title' => 'Why streams are better than foos?',
            'url' => 'https://example.com/article',
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'title' => 'Another subject',
            'url' => 'https://example.com/other',
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: 'foos');

        $links_timeline = $stream_view->linksTimeline();

        $links = $this->linksOfTimeline($links_timeline);
        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testLinksTimelineCanListLinksByQueryOnUrl(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'title' => 'Why streams are better than foos?',
            'url' => 'https://example.com/article',
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'title' => 'Another subject',
            'url' => 'https://example.org/article',
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: 'url:example.com');

        $links_timeline = $stream_view->linksTimeline();

        $links = $this->linksOfTimeline($links_timeline);
        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testLinksTimelineCanListLinksByQueryOnTag(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
            'tags' => ['foo'],
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
            'tags' => ['bar'],
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: '#foo');

        $links_timeline = $stream_view->linksTimeline();

        $links = $this->linksOfTimeline($links_timeline);
        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testLinksTimelineCanExcludeLinksByQueryOnTag(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
            'tags' => ['foo'],
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
            'tags' => ['bar'],
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: '-#bar');

        $links_timeline = $stream_view->linksTimeline();

        $links = $this->linksOfTimeline($links_timeline);
        $this->assertSame(1, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
    }

    public function testLinksTimelineIgnoresMalformedQuery(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: '""');

        $links_timeline = $stream_view->linksTimeline();

        $links = $this->linksOfTimeline($links_timeline);
        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testLinksTimelineCanListLinksFromPrivateSourceIfTheUserOwnsTheSource(): void
    {
        $date = $this->fakeDateInPeriod();
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date->format('Y-m-d'),
            'days' => 1,
        ]);
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
            'user_id' => $user->id,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
            'user_id' => $other_user->id,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = StreamView::buildFromRequest($stream, $user, $request);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source_1->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineCanListLinksFromPrivateSourceIfTheUserHasAccessToTheSource(): void
    {
        $date = $this->fakeDateInPeriod();
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date->format('Y-m-d'),
            'days' => 1,
        ]);
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
            'user_id' => $other_user->id,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
            'user_id' => $other_user->id,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $source_1->shareWith($user, 'read');
        $stream_view = StreamView::buildFromRequest($stream, $user, $request);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source_1->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineExcludesHiddenLinks(): void
    {
        $date = $this->fakeDateInPeriod();
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date->format('Y-m-d'),
            'days' => 1,
        ]);
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => true,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineExcludesPrivateSources(): void
    {
        $date = $this->fakeDateInPeriod();
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date->format('Y-m-d'),
            'days' => 1,
        ]);
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $links_timeline = $stream_view->linksTimeline();

        $date_groups = $links_timeline->datesGroups();
        $this->assertSame(1, count($date_groups));
        $date_group = array_shift($date_groups);
        $this->assertNotNull($date_group);
        $source_groups = $date_group->sourceGroups();
        $this->assertSame(1, count($source_groups));
        $source_group = $source_groups[0];
        $this->assertSame($source_1->id, $source_group->source->id);
        $this->assertSame(1, count($source_group->links));
        $this->assertSame($link_1->id, $source_group->links[0]->id);
    }

    public function testLinksTimelineIsEmptyIfStreamHasNoVisibleSource(): void
    {
        $date = $this->fakeDateInPeriod();
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date->format('Y-m-d'),
            'days' => 1,
        ]);
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream_view = StreamView::buildFromRequest($stream, null, $request);

        $links_timeline = $stream_view->linksTimeline();

        $this->assertTrue($links_timeline->empty());
    }

    public function testCountedSourcesReturnsSourcesWithTheirCounts(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link_1);
        $stream_view = new StreamView($stream, $user, at: $date);

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(1, count($sources_and_counts));
        list($counted_source, $count) = $sources_and_counts[0];
        $this->assertSame($source->id, $counted_source->id);
        $this->assertSame(2, $count);
    }

    public function testCountedSourcesCountsOnlyLinksMatchingTheStatus(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2, $link_3], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link_1);
        $user->markAsReadLater($link_2);
        $stream_view_unread = new StreamView($stream, $user, at: $date, status: 'unread');
        $stream_view_read = new StreamView($stream, $user, at: $date, status: 'read');
        $stream_view_read_later = new StreamView($stream, $user, at: $date, status: 'read-later');

        $sources_and_counts_unread = $stream_view_unread->countedSources();
        $sources_and_counts_read = $stream_view_read->countedSources();
        $sources_and_counts_read_later = $stream_view_read_later->countedSources();

        $this->assertSame(1, count($sources_and_counts_unread));
        $this->assertSame(1, $sources_and_counts_unread[0][1]);
        $this->assertSame(1, count($sources_and_counts_read));
        $this->assertSame(1, $sources_and_counts_read[0][1]);
        $this->assertSame(1, count($sources_and_counts_read_later));
        $this->assertSame(1, $sources_and_counts_read_later[0][1]);
    }

    public function testCountedSourcesCountsOnlyLinksMatchingTheQuery(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'title' => 'Why streams are better than foos?',
            'url' => 'https://example.com/article',
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'title' => 'Another subject',
            'url' => 'https://example.com/other',
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: 'foos');

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(1, count($sources_and_counts));
        list($counted_source, $count) = $sources_and_counts[0];
        $this->assertSame($source->id, $counted_source->id);
        $this->assertSame(1, $count);
    }

    public function testCountedSourcesExcludesSourcesWithoutLinksMatchingTheStatus(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $user->markAsRead($link_2);
        $stream_view = new StreamView($stream, $user, at: $date, status: 'unread');

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(1, count($sources_and_counts));
        $this->assertSame($source_1->id, $sources_and_counts[0][0]->id);
    }

    public function testCountedSourcesIgnoresTheStatusIfNoContextUser(): void
    {
        $date = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, null, at: $date, status: 'read');

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(1, count($sources_and_counts));
        list($counted_source, $count) = $sources_and_counts[0];
        $this->assertSame($source->id, $counted_source->id);
        $this->assertSame(1, $count);
    }

    public function testCountedSourcesExcludesSourcesWithoutLinksOverThePeriod(): void
    {
        $date = $this->fakeDateInPeriod();
        $other_date = $date->modify('-1 day');
        $stream = StreamFactory::create();
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $other_date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = new StreamView($stream, null, at: $date);

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(1, count($sources_and_counts));
        $this->assertSame($source_1->id, $sources_and_counts[0][0]->id);
    }

    public function testCountedSourcesSortsSourcesByName(): void
    {
        $date = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'name' => 'Feed A',
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'name' => 'Feed B',
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1, $link_2], at: $date);
        $source_2->addLinks([$link_3], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = new StreamView($stream, null, at: $date);

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(2, count($sources_and_counts));
        $this->assertSame($source_1->id, $sources_and_counts[0][0]->id);
        $this->assertSame(2, $sources_and_counts[0][1]);
        $this->assertSame($source_2->id, $sources_and_counts[1][0]->id);
        $this->assertSame(1, $sources_and_counts[1][1]);
    }

    public function testCountedSourcesExcludesPrivateSources(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = new StreamView($stream, $user, at: $date);

        $sources_and_counts = $stream_view->countedSources();

        $this->assertSame(1, count($sources_and_counts));
        $this->assertSame($source_1->id, $sources_and_counts[0][0]->id);
    }

    public function testCountByDayReturnsNumberOfLinksOnGivenDay(): void
    {
        $date_1 = $this->fakeDateInPeriod();
        $date_2 = $date_1->modify('-1 day');
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1], at: $date_1);
        $source->addLinks([$link_2, $link_3], at: $date_2);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, null, at: $date_1);

        $count_day_1 = $stream_view->countByDay($date_1);
        $count_day_2 = $stream_view->countByDay($date_2);

        $this->assertSame(1, $count_day_1);
        $this->assertSame(2, $count_day_2);
    }

    public function testCountByDayIgnoresTheQuery(): void
    {
        // The day picker displays the whole activity of the stream: its counts
        // must not depend on the search.
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'title' => 'Why streams are better than foos?',
            'url' => 'https://example.com/article',
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'title' => 'Another subject',
            'url' => 'https://example.com/other',
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, $user, at: $date, query: 'foos');

        $count = $stream_view->countByDay($date);

        $this->assertSame(2, $count);
    }

    public function testCountUnreadByDayReturnsNumberOfUnreadLinksOnGivenDay(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1, $link_2, $link_3], at: $date);
        $stream->addSource($source);
        $user->markAsRead($link_1);
        $stream_view = new StreamView($stream, $user, at: $date);

        $count_unread = $stream_view->countUnreadByDay($date);

        $this->assertSame(2, $count_unread);
    }

    public function testCountUnreadByDayDoesNotCountUnreadLinksIfNoContextUser(): void
    {
        $date = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, null, at: $date);

        $count_unread = $stream_view->countUnreadByDay($date);

        $this->assertSame(0, $count_unread);
    }

    public function testMaxCountPerDayReturnsTheMaxOfTheCountsByDay(): void
    {
        $date_1 = $this->fakeDateInPeriod();
        $date_2 = $date_1->modify('-1 day');
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link_1], at: $date_1);
        $source->addLinks([$link_2, $link_3], at: $date_2);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, null, at: $date_1);

        $max_count = $stream_view->maxCountPerDay();

        $this->assertSame(2, $max_count);
    }

    public function testCountByDayGroupsLinksInTheTimezoneOfTheApplication(): void
    {
        // The database groups the dates in its own timezone (UTC), so a link
        // published just after midnight locally would be counted on the
        // previous day if the timezone was not taken into account. The
        // timezone of the tests being UTC, it has to be changed to make this
        // case reachable.
        $initial_timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        try {
            $date = $this->fakeDateInPeriod()->modify('00:30:00');
            $stream = StreamFactory::create();
            $source = CollectionFactory::create([
                'type' => 'feed',
                'is_public' => true,
            ]);
            $link = LinkFactory::create([
                'is_hidden' => false,
            ]);
            $source->addLinks([$link], at: $date);
            $stream->addSource($source);
            $stream_view = new StreamView($stream, null, at: $date);

            $count_day = $stream_view->countByDay($date);
            $count_previous_day = $stream_view->countByDay($date->modify('-1 day'));

            $this->assertSame(1, $count_day);
            $this->assertSame(0, $count_previous_day);
        } finally {
            date_default_timezone_set($initial_timezone);
        }
    }

    public function testCountByDayExcludesHiddenLinks(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => true,
        ]);
        $source->addLinks([$link_1, $link_2], at: $date);
        $stream->addSource($source);
        $stream_view = new StreamView($stream, null, at: $date);

        $count_day = $stream_view->countByDay($date);

        $this->assertSame(1, $count_day);
    }

    public function testCountByDayExcludesPrivateSources(): void
    {
        $date = $this->fakeDateInPeriod();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source_1 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link_1 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source_1->addLinks([$link_1], at: $date);
        $source_2->addLinks([$link_2], at: $date);
        $stream->addSource($source_1);
        $stream->addSource($source_2);
        $stream_view = new StreamView($stream, null, at: $date);

        $count_day = $stream_view->countByDay($date);

        $this->assertSame(1, $count_day);
    }

    public function testStreamLinksCanExcludeLinksCreatedAfterAGivenDate(): void
    {
        $published_at = $this->fakeDateInPeriod();
        $before = \Minz\Time::now();
        $stream = StreamFactory::create();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_created_before = LinkFactory::create([
            'is_hidden' => false,
            'created_at' => \Minz\Time::ago(1, 'hour'),
        ]);
        $link_created_after = LinkFactory::create([
            'is_hidden' => false,
            'created_at' => \Minz\Time::fromNow(1, 'hour'),
        ]);
        $source->addLinks([$link_created_before, $link_created_after], at: $published_at);
        $stream->addSource($source);

        $links = $stream->links([
            'at' => $published_at,
            'created_before' => $before,
        ]);

        $this->assertSame(1, count($links));
        $this->assertSame($link_created_before->id, $links[0]->id);
    }

    public function testStreamLinksDoesNotExcludeLinksIfCreatedBeforeIsNull(): void
    {
        $published_at = $this->fakeDateInPeriod();
        $stream = StreamFactory::create();
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link_created_before = LinkFactory::create([
            'is_hidden' => false,
            'created_at' => \Minz\Time::ago(1, 'hour'),
        ]);
        $link_created_after = LinkFactory::create([
            'is_hidden' => false,
            'created_at' => \Minz\Time::fromNow(1, 'hour'),
        ]);
        $source->addLinks([$link_created_before, $link_created_after], at: $published_at);
        $stream->addSource($source);

        $links = $stream->links([
            'at' => $published_at,
            'created_before' => null,
        ]);

        $this->assertSame(2, count($links));
    }

    /**
     * Return the links of a timeline, without their groups.
     *
     * @return Link[]
     */
    private function linksOfTimeline(\App\utils\LinksTimeline $links_timeline): array
    {
        $links = [];

        foreach ($links_timeline->datesGroups() as $date_group) {
            foreach ($date_group->sourceGroups() as $source_group) {
                $links = array_merge($links, $source_group->links);
            }
        }

        return $links;
    }

    /**
     * Return a random date over the period covered by a StreamView, with
     * enough room to look for links on the previous days.
     */
    private function fakeDateInPeriod(): \DateTimeImmutable
    {
        /** @var int */
        $days_ago = $this->fake('numberBetween', 3, 28);

        return \Minz\Time::ago($days_ago, 'days');
    }

    /**
     * @return array<array{string, string}>
     */
    public static function atOutOfPeriodProvider(): array
    {
        return [
            ['+1 day', 'today'],
            ['-30 days', '-29 days'],
        ];
    }

    /**
     * @return array<array{int, int}>
     */
    public static function daysOutOfLimitsProvider(): array
    {
        return [
            [0, 1],
            [8, 7],
        ];
    }
}
