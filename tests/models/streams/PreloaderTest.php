<?php

namespace App\models\streams;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

/**
 * The preloaded values are indistinguishable from the ones the per-stream
 * methods would load by themselves: that is the point of the Preloader. So, to
 * prove that a value really comes from the memoizer cache, these tests delete
 * the data from the database after the preloading. A method that would still
 * query the database then returns nothing, and the test fails.
 */
class PreloaderTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testHasUnreadLinksForPreloadsTheValues(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $source = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $source->addLinks([$link], at: \Minz\Time::now());
        $stream->addSource($source);

        Preloader::for([$stream])->hasUnreadLinksFor($user);

        $link->remove();

        $this->assertTrue($stream->hasUnreadLinks($user));
    }
}
