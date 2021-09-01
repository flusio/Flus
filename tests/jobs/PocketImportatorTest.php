<?php

namespace flusio\jobs;

use flusio\models;

class PocketImportatorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

    public function testQueue()
    {
        $importator_job = new PocketImportator();

        $this->assertSame('importators', $importator_job->queue);
    }

    public function testImportPocketItemsImportInBookmarks()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks_id = $this->create('collection', [
            'type' => 'bookmarks',
            'user_id' => $user->id,
        ]);
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
            'collection_id' => $bookmarks_id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testImportPocketItemsDoesNotImportInBookmarksIfOption()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks_id = $this->create('collection', [
            'type' => 'bookmarks',
            'user_id' => $user->id,
        ]);
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
            'collection_id' => $bookmarks_id,
        ]);
        $this->assertNull($link_to_collection);
    }

    public function testImportPocketItemsImportInFavorite()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
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

    public function testImportPocketItemsDoesNotKeepFavoriteCollectionIfEmpty()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
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

    public function testImportPocketItemsDoesNotImportInFavoriteIfOption()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
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

    public function testImportPocketItemsImportInDefaultCollection()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
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

    public function testImportPocketItemsImportDoesNotKeepDefaultCollectionIfEmpty()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
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

    public function testImportPocketItemsDoesNotImportTags()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $url = $this->fake('url');
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

    public function testImportPocketItemsImportTagsIfOption()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $url = $this->fake('url');
        $tag1 = $this->fake('word');
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

    public function testImportPocketItemsUsesTimeAddedIfItExists()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks_id = $this->create('collection', [
            'type' => 'bookmarks',
            'user_id' => $user->id,
        ]);
        $url = $this->fake('url');
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
        $link_to_collection = models\LinkToCollection::findBy(['link_id' => $link->id]);
        $this->assertSame($time_added->getTimestamp(), $link_to_collection->created_at->getTimestamp());
    }

    public function testImportPocketItemsDoesNotDuplicateAGivenUrlAlreadyThere()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $given_url = $this->fakeUnique('url');
        $resolved_url = $this->fakeUnique('url');
        $previous_link_id = $this->create('link', [
            'url' => $given_url,
            'user_id' => $user->id,
        ]);
        $previous_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $previous_link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $previous_link_id,
            'collection_id' => $previous_collection_id,
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
        $this->assertSame($previous_link_id, $link->id);
        $this->assertTrue(models\LinkToCollection::exists($previous_link_to_collection_id));
        $favorite_collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $favorite_collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testImportPocketItemsDoesNotDuplicateAResolvedUrlAlreadyThere()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $given_url = $this->fakeUnique('url');
        $resolved_url = $this->fakeUnique('url');
        $previous_link_id = $this->create('link', [
            'url' => $resolved_url,
            'user_id' => $user->id,
        ]);
        $previous_collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $previous_link_to_collection_id = $this->create('link_to_collection', [
            'link_id' => $previous_link_id,
            'collection_id' => $previous_collection_id,
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
        $this->assertSame($previous_link_id, $link->id);
        $this->assertTrue(models\LinkToCollection::exists($previous_link_to_collection_id));
        $favorite_collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $favorite_collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testImportPocketItemsSetsResolvedTitle()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $url = $this->fake('url');
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
        $this->assertSame($title, $link->title);
    }

    public function testImportPocketItemsSetsGivenTitle()
    {
        $importator = new PocketImportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $url = $this->fake('url');
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
        $this->assertSame($title, $link->title);
    }
}
