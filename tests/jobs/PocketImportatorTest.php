<?php

namespace flusio\jobs;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class PocketImportatorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;

    public function testQueue(): void
    {
        $importator_job = new PocketImportator();

        $this->assertSame('importators', $importator_job->queue);
    }

    public function testImportPocketItemsImportInBookmarks(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '0',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testImportPocketItemsDoesNotImportInBookmarksIfOption(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '0',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => false,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $this->assertNull($link_to_collection);
    }

    public function testImportPocketItemsImportInFavorite(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '1',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $this->assertNotNull($collection);
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testImportPocketItemsDoesNotKeepFavoriteCollectionIfEmpty(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $this->assertNull($collection);
    }

    public function testImportPocketItemsDoesNotImportInFavoriteIfOption(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '1',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => false,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $this->assertNull($collection);
    }

    public function testImportPocketItemsImportInDefaultCollection(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $collection = models\Collection::findBy(['name' => 'Pocket links']);
        $this->assertNotNull($collection);
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testImportPocketItemsImportDoesNotKeepDefaultCollectionIfEmpty(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $items = [];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $collection = models\Collection::findBy(['name' => 'Pocket links']);
        $this->assertNull($collection);
    }

    public function testImportPocketItemsDoesNotImportTags(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $tag = $this->fake('word');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '1',
                'tags' => [
                    $tag => ['item_id' => 'some id', 'tag' => $tag],
                ],
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $collection = models\Collection::findBy(['name' => $tag]);
        $this->assertNull($collection);
    }

    public function testImportPocketItemsImportTagsIfOption(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $tag1 = $this->fake('word');
        /** @var string */
        $tag2 = $this->fake('word');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '1',
                'tags' => [
                    $tag1 => ['item_id' => 'some id 1', 'tag' => $tag1],
                    $tag2 => ['item_id' => 'some id 2', 'tag' => $tag2],
                ],
            ],
        ];
        $options = [
            'ignore_tags' => false,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $collection1 = models\Collection::findBy(['name' => $tag1]);
        $collection2 = models\Collection::findBy(['name' => $tag2]);
        $this->assertNotNull($collection1);
        $this->assertNotNull($collection2);
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $db_links_to_collection1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection1->id,
        ]);
        $db_links_to_collection2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection2->id,
        ]);
        $this->assertNotNull($db_links_to_collection1);
        $this->assertNotNull($db_links_to_collection2);
    }

    public function testImportPocketItemsUsesTimeAddedIfItExists(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $url = $this->fake('url');
        /** @var \DateTimeImmutable */
        $time_added = $this->fake('dateTime');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'favorite' => '0',
                'status' => '0',
                'time_added' => $time_added->getTimestamp(),
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $link_to_collection = models\LinkToCollection::findBy(['link_id' => $link->id]);
        $this->assertNotNull($link_to_collection);
        $this->assertEquals($time_added, $link_to_collection->created_at);
    }

    public function testImportPocketItemsDoesNotDuplicateAGivenUrlAlreadyThere(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $given_url = $this->fakeUnique('url');
        /** @var string */
        $resolved_url = $this->fakeUnique('url');
        $previous_link = LinkFactory::create([
            'url' => $given_url,
            'user_id' => $user->id,
        ]);
        $previous_collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $previous_link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $previous_link->id,
            'collection_id' => $previous_collection->id,
        ]);
        $items = [
            [
                'given_url' => $given_url,
                'resolved_url' => $resolved_url,
                'favorite' => '1',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $given_url]);
        $this->assertNotNull($link);
        $this->assertSame($previous_link->id, $link->id);
        $this->assertTrue(models\LinkToCollection::exists($previous_link_to_collection->id));
        $favorite_collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $this->assertNotNull($favorite_collection);
        $this->assertTrue(models\LinkToCollection::existsBy([
            'link_id' => $link->id,
            'collection_id' => $favorite_collection->id,
        ]));
    }

    public function testImportPocketItemsDoesNotDuplicateAResolvedUrlAlreadyThere(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $given_url = $this->fakeUnique('url');
        /** @var string */
        $resolved_url = $this->fakeUnique('url');
        $previous_link = LinkFactory::create([
            'url' => $resolved_url,
            'user_id' => $user->id,
        ]);
        $previous_collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $previous_link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $previous_link->id,
            'collection_id' => $previous_collection->id,
        ]);
        $items = [
            [
                'given_url' => $given_url,
                'resolved_url' => $resolved_url,
                'favorite' => '1',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $resolved_url]);
        $this->assertNotNull($link);
        $this->assertSame($previous_link->id, $link->id);
        $this->assertTrue(models\LinkToCollection::exists($previous_link_to_collection->id));
        $favorite_collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $this->assertNotNull($favorite_collection);
        $this->assertTrue(models\LinkToCollection::existsBy([
            'link_id' => $link->id,
            'collection_id' => $favorite_collection->id,
        ]));
    }

    public function testImportPocketItemsSetsResolvedTitle(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $title = $this->fake('sentence');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'resolved_title' => $title,
                'favorite' => '1',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $this->assertSame($title, $link->title);
    }

    public function testImportPocketItemsSetsGivenTitle(): void
    {
        $importator = new PocketImportator();
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $title = $this->fake('sentence');
        $items = [
            [
                'given_url' => $url,
                'resolved_url' => $url,
                'given_title' => $title,
                'favorite' => '1',
                'status' => '1',
            ],
        ];
        $options = [
            'ignore_tags' => true,
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        $importator->importPocketItems($user, $items, $options);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $this->assertSame($title, $link->title);
    }
}
