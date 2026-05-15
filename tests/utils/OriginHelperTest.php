<?php

namespace App\utils;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class OriginHelperTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;

    public function testExtractOriginFromPathWithExistingCollection(): void
    {
        $collection = CollectionFactory::create();
        $path = \Minz\Url::for('collection', ['id' => $collection->id]);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('collection', $origin_type);
        $this->assertSame($collection->id, $origin_id);
    }

    public function testExtractOriginFromPathWithExistingCollectionAndUrl(): void
    {
        $collection = CollectionFactory::create();
        $url = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($url);

        $this->assertSame('collection', $origin_type);
        $this->assertSame($collection->id, $origin_id);
    }

    public function testExtractOriginFromPathWithExistingUser(): void
    {
        $user = UserFactory::create();
        $path = \Minz\Url::for('profile', ['id' => $user->id]);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('user', $origin_type);
        $this->assertSame($user->id, $origin_id);
    }

    public function testExtractOriginFromPathWithExistingLink(): void
    {
        $link = LinkFactory::create();
        $path = \Minz\Url::for('link', ['id' => $link->id]);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('link', $origin_type);
        $this->assertSame($link->id, $origin_id);
    }

    public function testExtractOriginFromPathWithNonExistingCollection(): void
    {
        $path = \Minz\Url::for('collection', ['id' => '12345']);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('', $origin_type);
        $this->assertNull($origin_id);
    }

    public function testExtractOriginFromPathWithNonExistingUser(): void
    {
        $path = \Minz\Url::for('profile', ['id' => '12345']);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('', $origin_type);
        $this->assertNull($origin_id);
    }

    public function testExtractOriginFromPathWithNonExistingLink(): void
    {
        $path = \Minz\Url::for('link', ['id' => '12345']);

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('', $origin_type);
        $this->assertNull($origin_id);
    }

    public function testExtractOriginFromPathWithUnsupportedPath(): void
    {
        $path = \Minz\Url::for('bookmarks');

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($path);

        $this->assertSame('', $origin_type);
        $this->assertNull($origin_id);
    }

    public function testExtractOriginFromPathWithUnsupportedBaseUrl(): void
    {
        $collection = CollectionFactory::create();
        $path = \Minz\Url::for('collection', ['id' => $collection->id]);
        $url = 'https://not-the-domain.org' . $path;

        list($origin_type, $origin_id) = OriginHelper::extractFromPath($url);

        $this->assertSame('', $origin_type);
        $this->assertNull($origin_id);
    }
}
