<?php

namespace App\models\links;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

/**
 * The preloaded values are indistinguishable from the ones the per-link methods
 * would load by themselves: that is the point of the Preloader. So, to prove
 * that a value really comes from the memoizer cache, these tests delete the
 * data from the database after the preloading. A method that would still query
 * the database then returns nothing, and the test fails.
 */
class PreloaderTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testSourcesPreloadsTheSourceOfTheLinks(): void
    {
        $source = CollectionFactory::create([
            'type' => 'collection',
        ]);
        $link = LinkFactory::create([
            'source_id' => $source->id,
        ]);

        Preloader::for([$link])->sources();

        $source->remove();

        $this->assertSame($source->id, $link->source()?->id);
    }

    public function testSourcesPreloadsAFeedSource(): void
    {
        $source = CollectionFactory::create([
            'type' => 'feed',
        ]);
        $link = LinkFactory::create([
            'source_id' => $source->id,
        ]);

        Preloader::for([$link])->sources();

        $source->remove();

        $this->assertSame($source->id, $link->source()?->id);
    }

    public function testSourcesPreloadsNullWhenTheLinkHasNoSource(): void
    {
        $link = LinkFactory::create([
            'source_id' => null,
        ]);

        Preloader::for([$link])->sources();

        $this->assertNull($link->source());
    }

    public function testSourcesPreloadsNullWhenTheSourceIsDeleted(): void
    {
        $source = CollectionFactory::create([
            'type' => 'collection',
        ]);
        $link = LinkFactory::create([
            'source_id' => $source->id,
        ]);
        // Here the source is deleted *before* the preloading: the link keeps
        // its id in memory, while the column is set to null in database.
        $source->remove();

        Preloader::for([$link])->sources();

        $this->assertNull($link->source());
    }

    public function testSourcesSharesTheSourceBetweenTheLinks(): void
    {
        $source = CollectionFactory::create([
            'type' => 'collection',
        ]);
        $link_1 = LinkFactory::create([
            'source_id' => $source->id,
        ]);
        $link_2 = LinkFactory::create([
            'source_id' => $source->id,
        ]);

        Preloader::for([$link_1, $link_2])->sources();

        // Without the preloading, each link would load its own instance.
        $this->assertSame($link_1->source(), $link_2->source());
    }

    public function testCollectionsPreloadsTheCollectionsOfTheLinks(): void
    {
        $user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        // Only the "collection" type is preloaded, as collections() does.
        $feed = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'feed',
        ]);
        $link_in_two_collections = LinkFactory::create(['user_id' => $user->id]);
        $link_in_no_collection = LinkFactory::create(['user_id' => $user->id]);
        $link_in_two_collections->addCollections([$collection_1, $collection_2, $feed]);

        Preloader::for([$link_in_two_collections, $link_in_no_collection])->collections();

        $collection_1->remove();
        $collection_2->remove();
        $feed->remove();

        $expected_ids = [$collection_1->id, $collection_2->id];
        sort($expected_ids);
        $this->assertSame($expected_ids, $this->sortedCollectionIds($link_in_two_collections));
        $this->assertSame([], $link_in_no_collection->collections());
    }

    public function testCollectionsSharesTheCollectionsBetweenTheLinks(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_1 = LinkFactory::create(['user_id' => $user->id]);
        $link_2 = LinkFactory::create(['user_id' => $user->id]);
        $link_1->addCollection($collection);
        $link_2->addCollection($collection);

        Preloader::for([$link_1, $link_2])->collections();

        // Without the preloading, each link would load its own instance.
        $this->assertSame($link_1->collections()[0], $link_2->collections()[0]);
    }

    public function testUrlStatusesForPreloadsTheStatusesOfTheUser(): void
    {
        $user = UserFactory::create();
        $read_link = LinkFactory::create(['user_id' => $user->id]);
        $read_later_link = LinkFactory::create(['user_id' => $user->id]);
        $dismissed_link = LinkFactory::create(['user_id' => $user->id]);
        $unknown_link = LinkFactory::create(['user_id' => $user->id]);
        $user->markAsRead($read_link);
        $user->markAsReadLater($read_later_link);
        $user->markAsDismissed($dismissed_link);

        $links = [$read_link, $read_later_link, $dismissed_link, $unknown_link];
        // The statuses must be read from a user that never looked at them, so
        // that the preloaded values are the ones under test.
        $context_user = $user->reload();
        Preloader::for($links)->urlStatusesFor($context_user);

        models\UrlStatus::deleteBy(['user_id' => $user->id]);

        $this->assertTrue($context_user->hasRead($read_link));
        $this->assertTrue($context_user->hasReadLater($read_later_link));
        $this->assertTrue($context_user->hasDismissed($dismissed_link));
        $this->assertFalse($context_user->hasRead($unknown_link));
        $this->assertFalse($context_user->hasReadLater($unknown_link));
        $this->assertFalse($context_user->hasDismissed($unknown_link));
    }

    public function testUrlStatusesForIgnoresTheStatusesOfOtherUsers(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $other_link = LinkFactory::create(['user_id' => $other_user->id]);
        $other_user->markAsRead($other_link);
        // The link of the user has the same URL as the one read by the other.
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $other_link->url,
        ]);

        Preloader::for([$link])->urlStatusesFor($user);

        $this->assertFalse($user->hasRead($link));
    }

    public function testNumberCollectionsForPreloadsTheNumbersForUser(): void
    {
        $user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_in_two_collections = LinkFactory::create(['user_id' => $user->id]);
        $link_in_one_collection = LinkFactory::create(['user_id' => $user->id]);
        $link_in_no_collection = LinkFactory::create(['user_id' => $user->id]);
        $link_in_two_collections->addCollections([$collection_1, $collection_2]);
        $link_in_one_collection->addCollection($collection_1);

        $links = [$link_in_two_collections, $link_in_one_collection, $link_in_no_collection];
        Preloader::for($links)->numberCollectionsFor($user);

        $collection_1->remove();
        $collection_2->remove();

        $this->assertSame(2, $link_in_two_collections->numberCollectionsForUser($user));
        $this->assertSame(1, $link_in_one_collection->numberCollectionsForUser($user));
        $this->assertSame(0, $link_in_no_collection->numberCollectionsForUser($user));
    }

    public function testNumberCollectionsForIgnoresTheCollectionsOfOtherUsers(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $other_collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        $other_link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        $other_link->addCollection($other_collection);
        // The link of the user has the same URL, but is not in any collection.
        // url_hash is a generated column, so it is derived from the URL.
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $other_link->url,
        ]);

        Preloader::for([$link])->numberCollectionsFor($user);

        $this->assertSame(0, $link->numberCollectionsForUser($user));
    }

    public function testPreloadingAcceptsAnEmptyListOfLinks(): void
    {
        $user = UserFactory::create();

        $preloader = Preloader::for([])
            ->sources()
            ->collections()
            ->urlStatusesFor($user)
            ->numberCollectionsFor($user);

        $this->assertInstanceOf(Preloader::class, $preloader);
    }

    public function testPreloadingAcceptsANullUser(): void
    {
        $link = LinkFactory::create();

        $preloader = Preloader::for([$link])
            ->urlStatusesFor(null)
            ->numberCollectionsFor(null);

        $this->assertInstanceOf(Preloader::class, $preloader);
    }

    /**
     * @return string[]
     */
    private function sortedCollectionIds(models\Link $link): array
    {
        $collection_ids = array_column($link->collections(), 'id');
        sort($collection_ids);
        return $collection_ids;
    }
}
