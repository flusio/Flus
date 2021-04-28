<?php

namespace flusio\jobs;

use flusio\models;

class OpmlImportatorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

    /**
     * @beforeClass
     */
    public static function setJobAdapterToDatabase()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
    }

    /**
     * @afterClass
     */
    public static function setJobAdapterToTest()
    {
        \Minz\Configuration::$application['job_adapter'] = 'test';
    }

    public function testQueue()
    {
        $importator_job = new OpmlImportator();

        $this->assertSame('importators', $importator_job->queue);
    }

    public function testPerformCreatesNewCollectionsFromOpmlFile()
    {
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $opml_filepath = $tmp_path . '/' . $tmp_filename;
        copy($example_filepath, $opml_filepath);

        $followed_collections_dao = new models\dao\FollowedCollection();
        $importator = new OpmlImportator();
        $user_id = $this->create('user');
        $support_user = models\User::supportUser();
        $user = models\User::find($user_id);
        $importation_id = $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => json_encode(['opml_filepath' => $opml_filepath]),
        ]);

        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, $followed_collections_dao->count());

        $importator->perform($importation_id);

        $importation = models\Importation::find($importation_id);
        $this->assertSame('finished', $importation->status);
        $this->assertSame(3, models\Collection::count());
        $collection1 = models\Collection::take(0);
        $this->assertSame($support_user->id, $collection1->user_id);
        $this->assertSame('feed', $collection1->type);
        $collection2 = models\Collection::take(1);
        $this->assertSame($support_user->id, $collection2->user_id);
        $this->assertSame('feed', $collection2->type);
        $collection3 = models\Collection::take(2);
        $this->assertSame($support_user->id, $collection3->user_id);
        $this->assertSame('feed', $collection3->type);
        $this->assertSame(3, $followed_collections_dao->count());
        $followed_collections = $followed_collections_dao->listAll();
        $this->assertSame($user->id, $followed_collections[0]['user_id']);
        $this->assertSame($collection1->id, $followed_collections[0]['collection_id']);
        $this->assertSame($user->id, $followed_collections[1]['user_id']);
        $this->assertSame($collection2->id, $followed_collections[1]['collection_id']);
        $this->assertSame($user->id, $followed_collections[2]['user_id']);
        $this->assertSame($collection3->id, $followed_collections[2]['collection_id']);
    }

    public function testPerformRemovesFile()
    {
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $opml_filepath = $tmp_path . '/' . $tmp_filename;
        copy($example_filepath, $opml_filepath);

        $importator = new OpmlImportator();
        $user_id = $this->create('user');
        $importation_id = $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user_id,
            'options' => json_encode(['opml_filepath' => $opml_filepath]),
        ]);

        $this->assertTrue(file_exists($opml_filepath));

        $importator->perform($importation_id);

        $this->assertFalse(file_exists($opml_filepath));
    }

    public function testPerformRegistersAFeedsFetcherJob()
    {
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $opml_filepath = $tmp_path . '/' . $tmp_filename;
        copy($example_filepath, $opml_filepath);

        $followed_collections_dao = new models\dao\FollowedCollection();
        $importator = new OpmlImportator();
        $user_id = $this->create('user');
        $support_user = models\User::supportUser();
        $user = models\User::find($user_id);
        $importation_id = $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => json_encode(['opml_filepath' => $opml_filepath]),
        ]);

        $importator->perform($importation_id);

        $job_dao = new models\dao\Job();
        $db_job = $job_dao->listAll()[0];
        $handler = json_decode($db_job['handler'], true);
        $this->assertSame('flusio\\jobs\\FeedsFetcher', $handler['job_class']);
    }

    public function testPerformDoesNotCreateExistingFeed()
    {
        $example_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $opml_filepath = $tmp_path . '/' . $tmp_filename;
        copy($example_filepath, $opml_filepath);

        $followed_collections_dao = new models\dao\FollowedCollection();
        $importator = new OpmlImportator();
        $user_id = $this->create('user');
        $support_user = models\User::supportUser();
        $user = models\User::find($user_id);
        $collection_id = $this->create('collection', [
            'user_id' => $support_user->id,
            'type' => 'feed',
            'feed_url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
        ]);
        $importation_id = $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => json_encode(['opml_filepath' => $opml_filepath]),
        ]);

        $this->assertSame(1, models\Collection::count());
        $this->assertSame(0, $followed_collections_dao->count());

        $importator->perform($importation_id);

        $this->assertSame(3, models\Collection::count());
        $followed_collection = $followed_collections_dao->findBy([
            'collection_id' => $collection_id,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($followed_collection);
    }

    public function testPerformHandlesIfImportationIsMissing()
    {
        $followed_collections_dao = new models\dao\FollowedCollection();
        $importator = new OpmlImportator();

        $importator->perform(1);

        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, $followed_collections_dao->count());
    }

    public function testPerformFailsIfFileIsMissing()
    {
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $opml_filepath = $tmp_path . '/' . $tmp_filename;

        $followed_collections_dao = new models\dao\FollowedCollection();
        $importator = new OpmlImportator();
        $user_id = $this->create('user');
        $support_user = models\User::supportUser();
        $user = models\User::find($user_id);
        $importation_id = $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => json_encode(['opml_filepath' => $opml_filepath]),
        ]);

        $importator->perform($importation_id);

        $importation = models\Importation::find($importation_id);
        $this->assertSame('error', $importation->status);
        $this->assertSame('Can’t read the OPML file.', $importation->error);
        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, $followed_collections_dao->count());
    }

    public function testPerformFailsIfFileIsNotOpml()
    {
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $opml_filepath = $tmp_path . '/' . $tmp_filename;
        file_put_contents($opml_filepath, 'not opml');

        $followed_collections_dao = new models\dao\FollowedCollection();
        $importator = new OpmlImportator();
        $user_id = $this->create('user');
        $support_user = models\User::supportUser();
        $user = models\User::find($user_id);
        $importation_id = $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'options' => json_encode(['opml_filepath' => $opml_filepath]),
        ]);

        $importator->perform($importation_id);

        $importation = models\Importation::find($importation_id);
        $this->assertSame('error', $importation->status);
        $this->assertSame('Can’t parse the given string.', $importation->error);
        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, $followed_collections_dao->count());
        $this->assertFalse(file_exists($opml_filepath));
    }
}
