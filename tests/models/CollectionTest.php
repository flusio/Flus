<?php

namespace App\models;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testSyncPublicationFrequencyPerYear(): void
    {
        $collection = CollectionFactory::create();
        $link = LinkFactory::create();
        $collection->addLinks([$link], at: \Minz\Time::ago(11, 'months'));

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(1, $collection->publication_frequency_per_year);
    }

    public function testSyncPublicationFrequencyPerYearWithSeveralPublicationsOverTheYear(): void
    {
        $collection = CollectionFactory::create();
        $link_1 = LinkFactory::create();
        $link_2 = LinkFactory::create();
        $link_3 = LinkFactory::create();
        $link_4 = LinkFactory::create();
        $link_5 = LinkFactory::create();
        $link_6 = LinkFactory::create();
        $collection->addLinks([$link_1], at: \Minz\Time::ago(1, 'month'));
        $collection->addLinks([$link_2], at: \Minz\Time::ago(2, 'months'));
        $collection->addLinks([$link_3], at: \Minz\Time::ago(3, 'months'));
        $collection->addLinks([$link_4], at: \Minz\Time::ago(4, 'months'));
        $collection->addLinks([$link_5], at: \Minz\Time::ago(5, 'months'));
        $collection->addLinks([$link_6], at: \Minz\Time::ago(6, 'months'));

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(12, $collection->publication_frequency_per_year);
    }

    public function testSyncPublicationFrequencyPerYearWithOnePublicationRightNow(): void
    {
        $collection = CollectionFactory::create();
        $link = LinkFactory::create();
        $collection->addLinks([$link], at: \Minz\Time::now());

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(365, $collection->publication_frequency_per_year);
    }

    public function testSyncPublicationFrequencyPerYearWithNoPublications(): void
    {
        $collection = CollectionFactory::create();

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(0, $collection->publication_frequency_per_year);
    }

    public function testSyncPublicationFrequencyPerYearWithNoPublicationsOverTheYear(): void
    {
        $collection = CollectionFactory::create();
        $link = LinkFactory::create();
        $collection->addLinks([$link], at: \Minz\Time::ago(13, 'months'));

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(0, $collection->publication_frequency_per_year);
    }
}
