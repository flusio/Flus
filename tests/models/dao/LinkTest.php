<?php

namespace flusio\models\dao;

use flusio\models;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;

    public function testBulkInsert()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $link_url_1 = $this->fake('url');
        $link_url_2 = $this->fake('url');
        $link_url_3 = $this->fake('url');
        $link_1 = models\Link::init($link_url_1, $user_id, true);
        $link_2 = models\Link::init($link_url_2, $user_id, true);
        $link_3 = models\Link::init($link_url_3, $user_id, false);
        $link_1->created_at = $this->fake('dateTime');
        $link_2->created_at = $this->fake('dateTime');
        $link_3->created_at = $this->fake('dateTime');
        $db_link_1 = $link_1->toValues();
        $db_link_2 = $link_2->toValues();
        $db_link_3 = $link_3->toValues();
        $columns = array_keys($db_link_1);
        $values = array_merge(
            array_values($db_link_1),
            array_values($db_link_2),
            array_values($db_link_3)
        );

        $this->assertSame(0, $dao->count());

        $result = $dao->bulkInsert($columns, $values);

        $this->assertTrue($result);
        $this->assertSame(3, $dao->count());
        $this->assertTrue($dao->exists($link_1->id));
        $this->assertTrue($dao->exists($link_2->id));
        $this->assertTrue($dao->exists($link_3->id));
    }

    public function testListComputedByUserId()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);

        $db_links = $dao->listComputedByUserId($user_id, []);

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id, $db_links[0]['id']);
    }

    public function testListComputedByUserIdCanReturnPublishedAt()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $db_links = $dao->listComputedByUserId($user_id, ['published_at']);

        $this->assertSame(1, count($db_links));
        $link_published_at = date_create_from_format(
            \Minz\Model::DATETIME_FORMAT,
            $db_links[0]['published_at']
        );
        $this->assertEquals($published_at, $link_published_at);
    }

    public function testListComputedByUserIdConsidersLinkToCollectionPublishedAtWhenUnsharedIsFalse()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $db_links = $dao->listComputedByUserId($user_id, ['published_at'], [
            'unshared' => false,
        ]);

        $this->assertSame(1, count($db_links));
        $link_published_at = date_create_from_format(
            \Minz\Model::DATETIME_FORMAT,
            $db_links[0]['published_at']
        );
        $this->assertEquals($published_at, $link_published_at);
    }

    public function testListComputedByUserIdCanReturnNumberComments()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $message = $this->create('message', [
            'link_id' => $link_id,
        ]);

        $db_links = $dao->listComputedByUserId($user_id, ['number_comments']);

        $this->assertSame(1, $db_links[0]['number_comments']);
    }

    public function testListComputedByUserIdCanReturnIsRead()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $read_list = $other_user->readList();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $url,
        ]);
        $read_link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $read_link_id,
            'collection_id' => $read_list->id,
        ]);

        $db_links = $dao->listComputedByUserId($user_id, ['is_read'], [
            'context_user_id' => $other_user_id,
        ]);

        $this->assertTrue($db_links[0]['is_read']);
    }

    public function testListComputedByUserIdCanListSharedOnly()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $unshared_link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
        ]);
        $unshared_link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 1,
        ]);
        $unshared_link_id_3 = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
        ]);
        $shared_link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
        ]);
        $private_collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'is_public' => 0,
        ]);
        $public_collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'is_public' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $unshared_link_id_2,
            'collection_id' => $public_collection_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $unshared_link_id_3,
            'collection_id' => $private_collection_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $shared_link_id,
            'collection_id' => $public_collection_id,
        ]);

        $db_links = $dao->listComputedByUserId($user_id, [], [
            'unshared' => false,
        ]);

        $this->assertSame(1, count($db_links));
        $this->assertSame($shared_link_id, $db_links[0]['id']);
    }

    public function testListComputedByUserIdCanLimitResults()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(3, 'days')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(2, 'days')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(1, 'days')->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $db_links = $dao->listComputedByUserId($user_id, [], [
            'limit' => 2,
        ]);

        $this->assertSame(2, count($db_links));
        $this->assertSame($link_id_3, $db_links[0]['id']);
        $this->assertSame($link_id_2, $db_links[1]['id']);
    }

    public function testListComputedByUserIdCanOffsetResults()
    {
        $dao = new Link();
        $user_id = $this->create('user');
        $link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(3, 'days')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(2, 'days')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(1, 'days')->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $db_links = $dao->listComputedByUserId($user_id, [], [
            'offset' => 1,
        ]);

        $this->assertSame(2, count($db_links));
        $this->assertSame($link_id_2, $db_links[0]['id']);
        $this->assertSame($link_id_1, $db_links[1]['id']);
    }
}
