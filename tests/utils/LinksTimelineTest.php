<?php

namespace flusio\utils;

use tests\factories\LinkFactory;

class LinksTimelineTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;

    public function testConstructGroupsLinksByDates(): void
    {
        $link1 = LinkFactory::create();
        $link1->published_at = new \DateTimeImmutable('2024-03-20 18:00');
        $link2 = LinkFactory::create();
        $link2->published_at = new \DateTimeImmutable('2024-03-22 12:00');
        $link3 = LinkFactory::create();
        $link3->published_at = new \DateTimeImmutable('2024-03-20 12:00');
        $links = [$link1, $link2, $link3];

        $timeline = new LinksTimeline($links);

        $this->assertFalse($timeline->empty());
        $dates_groups = $timeline->datesGroups();
        $this->assertSame(2, count($dates_groups));
        $group1 = $dates_groups['2024-03-20'];
        $group2 = $dates_groups['2024-03-22'];
        $this->assertEquals($link1->published_at, $group1->date);
        $this->assertSame(2, count($group1->links));
        $this->assertSame($link1->id, $group1->links[0]->id);
        $this->assertSame($link3->id, $group1->links[1]->id);
        $this->assertEquals($link2->published_at, $group2->date);
        $this->assertSame(1, count($group2->links));
        $this->assertSame($link2->id, $group2->links[0]->id);
    }

    public function testEmptyReturnsTrue(): void
    {
        $links = [];

        $timeline = new LinksTimeline($links);

        $this->assertTrue($timeline->empty());
    }

    public function testConstructIgnoresLinksWithoutPublishedAt(): void
    {
        $link = LinkFactory::create();
        $links = [$link];

        $timeline = new LinksTimeline($links);

        $this->assertTrue($timeline->empty());
    }
}
