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
        $title = $this->fake('words', 3, true);
        $query = $title;
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, []);

        $this->assertSame(1, count($db_links));
        $this->assertSame($link_id, $db_links[0]['id']);
        $this->assertGreaterThan(0, $db_links[0]['search_rank']);
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
        $this->assertGreaterThan(0, $db_links[0]['search_rank']);
    }

    public function testSearchComputedByUserIdSortsBySearchRank()
    {
        $dao = new dao\Link();
        $title_1 = 'foo';
        $title_2 = 'foo bar';
        $query = 'foo';
        $user_id = $this->create('user');
        $link_id_1 = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title_1,
            'url' => 'https://example.com/foo1', // urls must be of the same lenght
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title_2,
            'url' => 'https://example.com/foo2', // urls must be of the same lenght
        ]);

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, []);

        $this->assertSame(2, count($db_links));
        $this->assertSame($link_id_1, $db_links[0]['id']);
        $this->assertSame($link_id_2, $db_links[1]['id']);
        $this->assertGreaterThan($db_links[1]['search_rank'], $db_links[0]['search_rank']);
    }

    public function testSearchComputedByUserIdCanReturnPublishedAt()
    {
        $dao = new dao\Link();
        $title = $this->fake('words', 3, true);
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
        $title = $this->fake('words', 3, true);
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

    public function testSearchComputedByUserIdCanReturnIsRead()
    {
        $dao = new dao\Link();
        $title = $this->fake('words', 3, true);
        $url = $this->fake('url');
        $query = $title;
        $user_id = $this->create('user');
        $other_user_id = $this->create('user');
        $other_user = models\User::find($other_user_id);
        $read_list = $other_user->readList();
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $title,
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

        $db_links = $dao->listComputedByQueryAndUserId($query, $user_id, ['is_read'], [
            'context_user_id' => $other_user_id,
        ]);

        $this->assertSame(1, count($db_links));
        $this->assertTrue($db_links[0]['is_read']);
    }

    public function testSearchComputedByUserIdCanLimitResults()
    {
        $dao = new dao\Link();
        $title = $this->fake('words', 3, true);
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
        $title = $this->fake('words', 3, true);
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
        $title = $this->fake('words', 3, true);
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
