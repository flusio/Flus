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

    public function testPublicationFrequencyPerYear(): void
    {
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(11, 'months'),
        ]);

        $frequency_per_year = $collection->publicationFrequencyPerYear();

        $this->assertSame(1, $frequency_per_year);
    }

    public function testPublicationFrequencyPerYearWithSeveralPublicationsOverTheYear(): void
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

        $frequency_per_year = $collection->publicationFrequencyPerYear();

        $this->assertSame(12, $frequency_per_year);
    }

    public function testPublicationFrequencyPerYearWithOnePublicationRightNow(): void
    {
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::now(),
        ]);

        $frequency_per_year = $collection->publicationFrequencyPerYear();

        $this->assertSame(365, $frequency_per_year);
    }

    public function testPublicationFrequencyPerYearWithNoPublications(): void
    {
        $collection = CollectionFactory::create();

        $frequency_per_year = $collection->publicationFrequencyPerYear();

        $this->assertSame(0, $frequency_per_year);
    }

    public function testPublicationFrequencyPerYearWithNoPublicationsOverTheYear(): void
    {
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'created_at' => \Minz\Time::ago(13, 'months'),
        ]);

        $frequency_per_year = $collection->publicationFrequencyPerYear();

        $this->assertSame(0, $frequency_per_year);
    }
}
