<?php

namespace flusio\utils;

class ViaHelperTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;

    public function testExtractViaFromPathWithExistingCollection()
    {
        $collection_id = $this->create('collection');
        $path = \Minz\Url::for('collection', ['id' => $collection_id]);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('collection', $via_type);
        $this->assertSame($collection_id, $via_resource_id);
    }

    public function testExtractViaFromPathWithExistingUser()
    {
        $user_id = $this->create('user');
        $path = \Minz\Url::for('profile', ['id' => $user_id]);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('user', $via_type);
        $this->assertSame($user_id, $via_resource_id);
    }

    public function testExtractViaFromPathWithNonExistingCollection()
    {
        $path = \Minz\Url::for('collection', ['id' => '12345']);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('', $via_type);
        $this->assertNull($via_resource_id);
    }

    public function testExtractViaFromPathWithNonExistingUser()
    {
        $path = \Minz\Url::for('profile', ['id' => '12345']);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('', $via_type);
        $this->assertNull($via_resource_id);
    }

    public function testExtractViaFromPathWithUnsupportedPath()
    {
        $path = \Minz\Url::for('bookmarks');

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('', $via_type);
        $this->assertNull($via_resource_id);
    }
}
