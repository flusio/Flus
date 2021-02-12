<?php

namespace flusio\jobs;

use flusio\models;

class ImportatorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

    public function testImportPocketItemsImportInBookmarks()
    {
        $importator = new Importator();
        $links_to_collections_dao = new models\dao\LinksToCollections();
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

        $importator->importPocketItems($user, $items);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $db_links_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link->id,
            'collection_id' => $bookmarks_id,
        ]);
        $this->assertNotNull($db_links_to_collection);
    }

    public function testImportPocketItemsImportInFavorite()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $importator = new Importator();

        $importator->importPocketItems($user, $items);

        $collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $this->assertNotNull($collection);
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $db_links_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($db_links_to_collection);
    }

    public function testImportPocketItemsImportInDefaultCollection()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $importator = new Importator();

        $importator->importPocketItems($user, $items);

        $collection = models\Collection::findBy(['name' => 'Pocket links']);
        $this->assertNotNull($collection);
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $db_links_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($db_links_to_collection);
    }

    public function testImportPocketItemsDoesNotDuplicateAGivenUrlAlreadyThere()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $importator = new Importator();

        $importator->importPocketItems($user, $items);

        $link = models\Link::findBy(['url' => $given_url]);
        $this->assertSame($previous_link_id, $link->id);
        $this->assertTrue($links_to_collections_dao->exists($previous_link_to_collection_id));
        $favorite_collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $db_links_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link->id,
            'collection_id' => $favorite_collection->id,
        ]);
        $this->assertNotNull($db_links_to_collection);
    }

    public function testImportPocketItemsDoesNotDuplicateAResolvedUrlAlreadyThere()
    {
        $links_to_collections_dao = new models\dao\LinksToCollections();
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
        $importator = new Importator();

        $importator->importPocketItems($user, $items);

        $link = models\Link::findBy(['url' => $resolved_url]);
        $this->assertSame($previous_link_id, $link->id);
        $this->assertTrue($links_to_collections_dao->exists($previous_link_to_collection_id));
        $favorite_collection = models\Collection::findBy(['name' => 'Pocket favorite']);
        $db_links_to_collection = $links_to_collections_dao->findBy([
            'link_id' => $link->id,
            'collection_id' => $favorite_collection->id,
        ]);
        $this->assertNotNull($db_links_to_collection);
    }

    public function testImportPocketItemsSetsResolvedTitle()
    {
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
        $importator = new Importator();

        $importator->importPocketItems($user, $items);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertSame($title, $link->title);
    }

    public function testImportPocketItemsSetsGivenTitle()
    {
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
        $importator = new Importator();

        $importator->importPocketItems($user, $items);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertSame($title, $link->title);
    }
}
