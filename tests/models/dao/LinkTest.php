<?php

namespace flusio\models\dao;

use flusio\models;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

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
}
