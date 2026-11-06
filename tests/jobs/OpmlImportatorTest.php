<?php

namespace App\jobs;

use App\models;
use tests\factories\FeedFactory;
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

        $this->assertSame(0, models\Feed::count());
        $this->assertSame(0, models\Stream::count());
        $this->assertSame(0, models\Follow::count());

        $importator->perform($importation->id);

        $this->assertSame(3, models\Feed::count());
        $this->assertSame(1, models\Stream::count());
        $this->assertSame(3, models\Follow::count());

        $importation = $importation->reload();
        $this->assertSame('finished', $importation->status);
        $feed1 = models\Feed::take(0);
        $this->assertNotNull($feed1);
        $this->assertSame('feed', $feed1->type);
        $feed2 = models\Feed::take(1);
        $this->assertNotNull($feed2);
        $this->assertSame('feed', $feed2->type);
        $feed3 = models\Feed::take(2);
        $this->assertNotNull($feed3);
        $this->assertSame('feed', $feed3->type);
        $follow1 = models\Follow::take(0);
        $this->assertNotNull($follow1);
        $this->assertSame($user->id, $follow1->user_id);
        $this->assertSame($feed1->id, $follow1->collection_id);
        $follow2 = models\Follow::take(1);
        $this->assertNotNull($follow2);
        $this->assertSame($user->id, $follow2->user_id);
        $this->assertSame($feed2->id, $follow2->collection_id);
        $follow3 = models\Follow::take(2);
        $this->assertNotNull($follow3);
        $this->assertSame($user->id, $follow3->user_id);
        $this->assertSame($feed3->id, $follow3->collection_id);
        $stream = models\Stream::take();
        $this->assertNotNull($stream);
        $this->assertSame('Blogs', $stream->name);
        $this->assertSame($user->id, $stream->user_id);
        $this->assertTrue($stream->hasSource($feed1));
        $this->assertTrue($stream->hasSource($feed2));
        $this->assertTrue($stream->hasSource($feed3));
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
        $this->assertSame(0, models\Feed::count());

        $importator->perform($importation->id);

        $this->assertSame(1, models\Stream::count());
        $this->assertSame(3, models\Feed::count());

        $feed = models\Feed::take(0);
        $this->assertNotNull($feed);
        $this->assertTrue($stream->hasSource($feed));
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

        $this->assertSame(3, models\Feed::count());
        $this->assertSame(1, models\Stream::count());
        $this->assertSame(3, models\Follow::count());
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
        $feed = FeedFactory::create([
            'feed_url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
        ]);
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $this->assertSame(1, models\Feed::count());
        $this->assertSame(0, models\Follow::count());

        $importator->perform($importation->id);

        $this->assertSame(3, models\Feed::count());
        $follow = models\Follow::findBy([
            'collection_id' => $feed->id,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($follow);
    }

    public function testPerformHandlesIfImportationIsMissing(): void
    {
        $importator = new OpmlImportator();

        $importator->perform(1);

        $this->assertSame(0, models\Feed::count());
        $this->assertSame(0, models\Follow::count());
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
        $this->assertSame(0, models\Feed::count());
        $this->assertSame(0, models\Follow::count());
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
        $this->assertSame(0, models\Feed::count());
        $this->assertSame(0, models\Follow::count());
        $this->assertFalse(file_exists($opml_filepath));
    }
}
