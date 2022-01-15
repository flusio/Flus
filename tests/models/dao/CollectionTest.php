<?php

namespace flusio\models\dao;

use flusio\models;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;

    public function testListComputedByUserId()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, []);

        $this->assertSame(1, count($db_collections));
        $this->assertSame($collection_id, $db_collections[0]['id']);
    }

    public function testListComputedByUserIdDoesNotReturnFeeds()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'feed',
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, []);

        $this->assertSame(0, count($db_collections));
    }

    public function testListComputedByUserIdCanFilterByGroupId()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $group_id = $this->create('group', [
            'user_id' => $user_id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'group_id' => $group_id,
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'group_id' => null,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, [], [
            'group' => $group_id,
        ]);

        $this->assertSame(1, count($db_collections));
        $this->assertSame($collection_id_1, $db_collections[0]['id']);
    }

    public function testListComputedByUserIdCanFilterByNullGroupId()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $group_id = $this->create('group', [
            'user_id' => $user_id,
        ]);
        $collection_id_1 = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'group_id' => $group_id,
        ]);
        $collection_id_2 = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'group_id' => null,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, [], [
            'group' => null,
        ]);

        $this->assertSame(1, count($db_collections));
        $this->assertSame($collection_id_2, $db_collections[0]['id']);
    }

    public function testListComputedByUserIdCanExcludePrivateCollections()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 0,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, [], [
            'private' => false,
        ]);

        $this->assertSame(0, count($db_collections));
    }

    public function testListComputedByUserIdCanReturnNumberLinks()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, ['number_links']);

        $this->assertSame(1, count($db_collections));
        $this->assertSame($collection_id, $db_collections[0]['id']);
        $this->assertSame(1, $db_collections[0]['number_links']);
    }

    public function testListComputedByUserIdCanExcludeHiddenLinks()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, ['number_links'], [
            'count_hidden' => false,
        ]);

        $this->assertSame(1, count($db_collections));
        $this->assertSame($collection_id, $db_collections[0]['id']);
        $this->assertSame(0, $db_collections[0]['number_links']);
    }

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollections()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, ['number_links'], [
            'private' => false,
        ]);

        $this->assertSame(0, count($db_collections));
    }

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollectionsAndConsiderCountHidden()
    {
        $dao = new Collection();
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $db_collections = $dao->listComputedByUserId($user_id, ['number_links'], [
            'private' => false,
            'count_hidden' => false,
        ]);

        $this->assertSame(0, count($db_collections));
    }
}
