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

    public function testBuildFromRequestSetsOptionsFromRequestParameters(): void
    {
        /** @var string */
        $date = $this->fake('date');
        /** @var int */
        $days = $this->fake('numberBetween', 1, 7);
        $request = new \Minz\Request('GET', '/stream', [
            'at' => $date,
            'days' => $days,
        ]);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, $request);

        $this->assertSame($date, $stream_view->at->format('Y-m-d'));
        $this->assertSame($days, $stream_view->days);
    }

    public function testBuildFromRequestHasDefaultValues(): void
    {
        $now = \Minz\Time::now();
        $request = new \Minz\Request('GET', '/stream', []);
        $stream = StreamFactory::create();

        $stream_view = StreamView::buildFromRequest($stream, $request);

        $this->assertSame($now->format('Y-m-d'), $stream_view->at->format('Y-m-d'));
        $this->assertSame(1, $stream_view->days);
    }

    public function testIsAt(): void
    {
        /** @var \DateTimeImmutable */
        $date_1 = $this->fake('dateTime');
        $date_2 = $date_1->modify('-1 day');
        $stream = StreamFactory::create();
        $stream_view = new StreamView($stream, at: $date_1);

        $is_at_date_1 = $stream_view->isAt($date_1);
        $is_at_date_2 = $stream_view->isAt($date_2);

        $this->assertTrue($is_at_date_1);
        $this->assertFalse($is_at_date_2);
    }

    public function testPeriod(): void
    {
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
        $stream = StreamFactory::create();
        $stream_view = new StreamView($stream, at: $date);

        $period = $stream_view->period();

        $expected_first_day = \Minz\Time::now();
        $expected_last_day = \Minz\Time::ago(29, 'days');
        $this->assertSame(30, count($period));
        $this->assertSame($expected_first_day->format('Y-m-d'), $period[0]->format('Y-m-d'));
        $this->assertSame($expected_last_day->format('Y-m-d'), $period[29]->format('Y-m-d'));
    }

    public function testLinksTimelineListsLinksOfSelectedDay(): void
    {
        /** @var \DateTimeImmutable */
        $date_1 = $this->fake('dateTime');
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
        $stream_view = StreamView::buildFromRequest($stream, $request);

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
        /** @var \DateTimeImmutable */
        $date_1 = $this->fake('dateTime');
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
        $stream_view = StreamView::buildFromRequest($stream, $request);

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

    public function testLinksTimelineCanListLinksFromPrivateSourceIfTheUserOwnsTheSource(): void
    {
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
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
        $stream_view = StreamView::buildFromRequest($stream, $request);

        $links_timeline = $stream_view->linksTimeline($user);

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
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
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
        $stream_view = StreamView::buildFromRequest($stream, $request);

        $links_timeline = $stream_view->linksTimeline($user);

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
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
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
        $stream_view = StreamView::buildFromRequest($stream, $request);

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
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
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
        $stream_view = StreamView::buildFromRequest($stream, $request);

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

    public function testCountByDayReturnsNumberOfLinksOnGivenDay(): void
    {
        /** @var \DateTimeImmutable */
        $date_1 = $this->fake('dateTime');
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
        $stream_view = new StreamView($stream, at: $date_1);

        $count_day_1 = $stream_view->countByDay($date_1);
        $count_day_2 = $stream_view->countByDay($date_2);

        $this->assertSame(1, $count_day_1);
        $this->assertSame(2, $count_day_2);
    }

    public function testCountByDayExcludesHiddenLinks(): void
    {
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
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
        $stream_view = new StreamView($stream, at: $date);

        $count_day = $stream_view->countByDay($date);

        $this->assertSame(1, $count_day);
    }

    public function testCountByDayExcludesPrivateSources(): void
    {
        /** @var \DateTimeImmutable */
        $date = $this->fake('dateTime');
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
        $stream_view = new StreamView($stream, at: $date);

        $count_day = $stream_view->countByDay($date);

        $this->assertSame(1, $count_day);
    }
}
