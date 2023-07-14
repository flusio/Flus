<?php

namespace flusio\utils;

use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class ViaHelperTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;

    public function testExtractViaFromPathWithExistingCollection(): void
    {
        $collection = CollectionFactory::create();
        $path = \Minz\Url::for('collection', ['id' => $collection->id]);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('collection', $via_type);
        $this->assertSame($collection->id, $via_resource_id);
    }

    public function testExtractViaFromPathWithExistingUser(): void
    {
        $user = UserFactory::create();
        $path = \Minz\Url::for('profile', ['id' => $user->id]);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('user', $via_type);
        $this->assertSame($user->id, $via_resource_id);
    }

    public function testExtractViaFromPathWithNonExistingCollection(): void
    {
        $path = \Minz\Url::for('collection', ['id' => '12345']);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('', $via_type);
        $this->assertNull($via_resource_id);
    }

    public function testExtractViaFromPathWithNonExistingUser(): void
    {
        $path = \Minz\Url::for('profile', ['id' => '12345']);

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('', $via_type);
        $this->assertNull($via_resource_id);
    }

    public function testExtractViaFromPathWithUnsupportedPath(): void
    {
        $path = \Minz\Url::for('bookmarks');

        list($via_type, $via_resource_id) = ViaHelper::extractFromPath($path);

        $this->assertSame('', $via_type);
        $this->assertNull($via_resource_id);
    }
}
