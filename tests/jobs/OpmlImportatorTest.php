<?php

namespace App\jobs;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\ImportationFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class OpmlImportatorTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setJobAdapterToDatabase(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function setJobAdapterToTest(): void
    {
        \App\Configuration::$jobs_adapter = 'test';
    }

    public function testQueue(): void
    {
        $importator_job = new OpmlImportator();

        $this->assertSame('importators', $importator_job->queue);
    }

    public function testPerformCreatesNewCollectionsAndStreamsFromOpmlFile(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\Stream::count());
        $this->assertSame(0, models\FollowedCollection::count());

        $importator->perform($importation->id);

        $this->assertSame(3, models\Collection::count());
        $this->assertSame(1, models\Stream::count());
        $this->assertSame(3, models\FollowedCollection::count());

        $importation = $importation->reload();
        $this->assertSame('finished', $importation->status);
        $collection1 = models\Collection::take(0);
        $this->assertNotNull($collection1);
        $this->assertNull($collection1->user_id);
        $this->assertSame('feed', $collection1->type);
        $collection2 = models\Collection::take(1);
        $this->assertNotNull($collection2);
        $this->assertNull($collection2->user_id);
        $this->assertSame('feed', $collection2->type);
        $collection3 = models\Collection::take(2);
        $this->assertNotNull($collection3);
        $this->assertNull($collection3->user_id);
        $this->assertSame('feed', $collection3->type);
        $followed_collection1 = models\FollowedCollection::take(0);
        $this->assertNotNull($followed_collection1);
        $this->assertSame($user->id, $followed_collection1->user_id);
        $this->assertSame($collection1->id, $followed_collection1->collection_id);
        $followed_collection2 = models\FollowedCollection::take(1);
        $this->assertNotNull($followed_collection2);
        $this->assertSame($user->id, $followed_collection2->user_id);
        $this->assertSame($collection2->id, $followed_collection2->collection_id);
        $followed_collection3 = models\FollowedCollection::take(2);
        $this->assertNotNull($followed_collection3);
        $this->assertSame($user->id, $followed_collection3->user_id);
        $this->assertSame($collection3->id, $followed_collection3->collection_id);
        $stream = models\Stream::take();
        $this->assertNotNull($stream);
        $this->assertSame('Blogs', $stream->name);
        $this->assertSame($user->id, $stream->user_id);
        $this->assertTrue($stream->hasSource($collection1));
        $this->assertTrue($stream->hasSource($collection2));
        $this->assertTrue($stream->hasSource($collection3));
    }

    public function testPerformReusesExistingStreamWithSameName(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'Blogs',
        ]);
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $this->assertSame(1, models\Stream::count());
        $this->assertSame(0, models\Collection::count());

        $importator->perform($importation->id);

        $this->assertSame(1, models\Stream::count());
        $this->assertSame(3, models\Collection::count());

        $collection = models\Collection::take(0);
        $this->assertNotNull($collection);
        $this->assertTrue($stream->hasSource($collection));
    }

    public function testPerformIsIdempotent(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $opml_filepath_1 = $this->tmpCopyFile($example_filepath);
        $importation_1 = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath_1],
        ]);
        $opml_filepath_2 = $this->tmpCopyFile($example_filepath);
        $importation_2 = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath_2],
        ]);

        $importator->perform($importation_1->id);
        $importator->perform($importation_2->id);

        $this->assertSame(3, models\Collection::count());
        $this->assertSame(1, models\Stream::count());
        $this->assertSame(3, models\FollowedCollection::count());
        $this->assertSame(3, models\StreamToFollow::count());

        $importation_1 = $importation_1->reload();
        $this->assertSame('finished', $importation_1->status);
        $importation_2 = $importation_2->reload();
        $this->assertSame('finished', $importation_2->status);
    }

    public function testPerformRemovesFile(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $this->assertTrue(file_exists($opml_filepath));

        $importator->perform($importation->id);

        $this->assertFalse(file_exists($opml_filepath));
    }

    public function testPerformDoesNotCreateExistingFeed(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => null,
            'type' => 'feed',
            'feed_url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
        ]);
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $this->assertSame(1, models\Collection::count());
        $this->assertSame(0, models\FollowedCollection::count());

        $importator->perform($importation->id);

        $this->assertSame(3, models\Collection::count());
        $followed_collection = models\FollowedCollection::findBy([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($followed_collection);
    }

    public function testPerformHandlesIfImportationIsMissing(): void
    {
        $importator = new OpmlImportator();

        $importator->perform(1);

        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testPerformFailsIfFileIsMissing(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        unlink($opml_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $importator->perform($importation->id);

        $importation = $importation->reload();
        $this->assertSame('error', $importation->status);
        $this->assertSame('Can’t read the OPML file.', $importation->error);
        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testPerformFailsIfFileIsNotOpml(): void
    {
        $example_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        file_put_contents($opml_filepath, 'not opml');
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $importator->perform($importation->id);

        $importation = $importation->reload();
        $this->assertSame('error', $importation->status);
        $this->assertSame('Can’t parse the given string.', $importation->error);
        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\FollowedCollection::count());
        $this->assertFalse(file_exists($opml_filepath));
    }
}
