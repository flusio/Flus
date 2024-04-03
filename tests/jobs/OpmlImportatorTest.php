<?php

namespace App\jobs;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\ImportationFactory;
use tests\factories\UserFactory;

class OpmlImportatorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FilesHelper;
    use \tests\InitializerHelper;

    /**
     * @beforeClass
     */
    public static function setJobAdapterToDatabase(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
    }

    /**
     * @afterClass
     */
    public static function setJobAdapterToTest(): void
    {
        \Minz\Configuration::$jobs_adapter = 'test';
    }

    public function testQueue(): void
    {
        $importator_job = new OpmlImportator();

        $this->assertSame('importators', $importator_job->queue);
    }

    public function testPerformCreatesNewCollectionsAndGroupsFromOpmlFile(): void
    {
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $support_user = models\User::supportUser();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => ['opml_filepath' => $opml_filepath],
        ]);

        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\Group::count());
        $this->assertSame(0, models\FollowedCollection::count());

        $importator->perform($importation->id);

        $importation = $importation->reload();
        $this->assertNotNull($importation);
        $this->assertSame('finished', $importation->status);
        $this->assertSame(3, models\Collection::count());
        $collection1 = models\Collection::take(0);
        $this->assertNotNull($collection1);
        $this->assertSame($support_user->id, $collection1->user_id);
        $this->assertSame('feed', $collection1->type);
        $collection2 = models\Collection::take(1);
        $this->assertNotNull($collection2);
        $this->assertSame($support_user->id, $collection2->user_id);
        $this->assertSame('feed', $collection2->type);
        $collection3 = models\Collection::take(2);
        $this->assertNotNull($collection3);
        $this->assertSame($support_user->id, $collection3->user_id);
        $this->assertSame('feed', $collection3->type);
        $this->assertSame(1, models\Group::count());
        $group = models\Group::take();
        $this->assertNotNull($group);
        $this->assertSame('Blogs', $group->name);
        $this->assertSame($user->id, $group->user_id);
        $followed_collections = models\FollowedCollection::listAll();
        $this->assertSame(3, count($followed_collections));
        $this->assertSame($user->id, $followed_collections[0]->user_id);
        $this->assertSame($collection1->id, $followed_collections[0]->collection_id);
        $this->assertSame($group->id, $followed_collections[0]->group_id);
        $this->assertSame($user->id, $followed_collections[1]->user_id);
        $this->assertSame($collection2->id, $followed_collections[1]->collection_id);
        $this->assertSame($group->id, $followed_collections[1]->group_id);
        $this->assertSame($user->id, $followed_collections[2]->user_id);
        $this->assertSame($collection3->id, $followed_collections[2]->collection_id);
        $this->assertSame($group->id, $followed_collections[2]->group_id);
    }

    public function testPerformRemovesFile(): void
    {
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
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
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $support_user = models\User::supportUser();
        $collection = CollectionFactory::create([
            'user_id' => $support_user->id,
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
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        unlink($opml_filepath);
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $support_user = models\User::supportUser();
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
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $opml_filepath = $this->tmpCopyFile($example_filepath);
        file_put_contents($opml_filepath, 'not opml');
        $importator = new OpmlImportator();
        $user = UserFactory::create();
        $support_user = models\User::supportUser();
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
