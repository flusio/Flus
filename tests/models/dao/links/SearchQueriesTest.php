<?php

namespace flusio\models\dao\links;

use flusio\models;
use flusio\models\dao;

class SearchQueriesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;

    public function testSearchComputedByUserIdSearchesByTitle()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, []);

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id, $db_links[0]['id']);
    }

    public function testSearchComputedByUserIdSearchesByUrl()
    {
        $dao = new dao\Link();
        $url = $this->fake('url');
        $query = $url;
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $url,
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, []);

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id, $db_links[0]['id']);
    }

    public function testSearchComputedByUserIdSortsByCreatedAt()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $created_at_1 = \Minz\Time::ago(1, 'day');
        $created_at_2 = \Minz\Time::ago(2, 'day');
        $link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
            'created_at' => $created_at_1->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
            'created_at' => $created_at_2->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, []);

        $this->assertSame(2, count($db_links));
        $this->assertSame($link_id_1, $db_links[0]['id']);
        $this->assertSame($link_id_2, $db_links[1]['id']);
    }

    public function testSearchComputedByUserIdCanReturnPublishedAt()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, ['published_at']);

        $this->assertSame(1, count($db_links));
        $link_published_at = date_create_from_format(
            \Minz\Model::DATETIME_FORMAT,
            $db_links[0]['published_at']
        );
        $this->assertEquals($published_at, $link_published_at);
    }

    public function testSearchComputedByUserIdCanReturnNumberComments()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
        ]);
        $message_id = $this->create('message', [
            'link_id' => $link_id,
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, ['number_comments']);

        $this->assertSame(1, count($db_links));
        $this->assertSame(1, $db_links[0]['number_comments']);
    }

    public function testSearchComputedByUserIdCanLimitResults()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(3, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'title' => $title,
            'url' => 'https://example.com/foo1', // urls must be of the same lenght
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(2, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'title' => $title,
            'url' => 'https://example.com/foo2', // urls must be of the same lenght
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(1, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'title' => $title,
            'url' => 'https://example.com/foo3', // urls must be of the same lenght
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, [], [
            'limit' => 2,
        ]);

        $this->assertSame(2, count($db_links));
        $this->assertSame($link_id_3, $db_links[0]['id']);
        $this->assertSame($link_id_2, $db_links[1]['id']);
    }

    public function testSearchComputedByUserIdCanOffsetResults()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(3, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'title' => $title,
            'url' => 'https://example.com/foo1', // urls must be of the same lenght
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(2, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'title' => $title,
            'url' => 'https://example.com/foo2', // urls must be of the same lenght
        ]);
        $link_id_3 = $this->create('link', [
            'user_id' => $user_id,
            'created_at' => \Minz\Time::ago(1, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'title' => $title,
            'url' => 'https://example.com/foo3', // urls must be of the same lenght
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, [], [
            'offset' => 1,
        ]);

        $this->assertSame(2, count($db_links));
        $this->assertSame($link_id_2, $db_links[0]['id']);
        $this->assertSame($link_id_1, $db_links[1]['id']);
    }

    public function testCountByUserId()
    {
        $dao = new dao\Link();
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
        ]);

        $count = $dao->countByQueryAndUserId($query, $user_id);

        $this->assertSame(1, $count);
    }
}
