<?php

namespace App\models;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testSyncPublicationFrequencyPerYear(): void
    {
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(11, 'months'),
        ]);

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(1, $collection->publication_frequency_per_year);
    }

    public function testSyncPublicationFrequencyPerYearWithSeveralPublicationsOverTheYear(): void
    {
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(1, 'month'),
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(2, 'months'),
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(3, 'months'),
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(4, 'months'),
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(5, 'months'),
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(6, 'months'),
        ]);

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(12, $collection->publication_frequency_per_year);
    }

    public function testSyncPublicationFrequencyPerYearWithOnePublicationRightNow(): void
    {
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::now(),
        ]);

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
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(13, 'months'),
        ]);

        $collection->syncPublicationFrequencyPerYear();

        $this->assertSame(0, $collection->publication_frequency_per_year);
    }
}
