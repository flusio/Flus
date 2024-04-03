<?php

namespace App\utils;

use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class SourceHelperTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;

    public function testExtractSourceFromPathWithExistingCollection(): void
    {
        $collection = CollectionFactory::create();
        $path = \Minz\Url::for('collection', ['id' => $collection->id]);

        list($source_type, $source_resource_id) = SourceHelper::extractFromPath($path);

        $this->assertSame('collection', $source_type);
        $this->assertSame($collection->id, $source_resource_id);
    }

    public function testExtractSourceFromPathWithExistingUser(): void
    {
        $user = UserFactory::create();
        $path = \Minz\Url::for('profile', ['id' => $user->id]);

        list($source_type, $source_resource_id) = SourceHelper::extractFromPath($path);

        $this->assertSame('user', $source_type);
        $this->assertSame($user->id, $source_resource_id);
    }

    public function testExtractSourceFromPathWithNonExistingCollection(): void
    {
        $path = \Minz\Url::for('collection', ['id' => '12345']);

        list($source_type, $source_resource_id) = SourceHelper::extractFromPath($path);

        $this->assertSame('', $source_type);
        $this->assertNull($source_resource_id);
    }

    public function testExtractSourceFromPathWithNonExistingUser(): void
    {
        $path = \Minz\Url::for('profile', ['id' => '12345']);

        list($source_type, $source_resource_id) = SourceHelper::extractFromPath($path);

        $this->assertSame('', $source_type);
        $this->assertNull($source_resource_id);
    }

    public function testExtractSourceFromPathWithUnsupportedPath(): void
    {
        $path = \Minz\Url::for('bookmarks');

        list($source_type, $source_resource_id) = SourceHelper::extractFromPath($path);

        $this->assertSame('', $source_type);
        $this->assertNull($source_resource_id);
    }
}
