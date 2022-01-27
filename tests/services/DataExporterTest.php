<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

class DataExporterTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    /** @var string */
    private $exportations_path;

    /**
     * @beforeClass
     */
    public static function setRouterToUrl()
    {
        $router = \flusio\Router::load();
        \Minz\Url::setRouter($router);
    }

    /**
     * @before
     */
    public function setExportationsPath()
    {
        $tmp_path = \Minz\Configuration::$tmp_path;
        $this->exportations_path = $tmp_path . '/' . md5(rand());
        @mkdir($this->exportations_path, 0777, true);
    }

    public function testConstructFailsIfPathDoesNotExist()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The path does not exist');

        new DataExporter('not a path');
    }

    public function testConstructFailsIfPathIsNotADirectory()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The path is not a directory');

        $tmp_path = \Minz\Configuration::$tmp_path;
        $path = $tmp_path . '/' . md5(rand());
        touch($path);

        new DataExporter($path);
    }

    public function testExportReturnsTheDataFilepath()
    {
        $this->freeze($this->fake('dateTime'));
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');

        $filepath = $data_exporter->export($user_id);

        $datetime = \Minz\Time::now()->format('Y-m-d_H\hi');
        $expected_filepath = $this->exportations_path . "/{$datetime}_{$user_id}_data.zip";
        $this->assertSame($expected_filepath, $filepath);
    }

    public function testExportCreatesTheDataFile()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');

        $filepath = $data_exporter->export($user_id);

        $this->assertTrue(file_exists($filepath));
    }

    public function testExportCreatesMetadata()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');

        $filepath = $data_exporter->export($user_id);

        $metadata_content = $this->zipGetContents($filepath, 'metadata.json');
        $metadata = json_decode($metadata_content, true);
        $this->assertSame(\Minz\Configuration::$application['user_agent'], $metadata['generator']);
    }

    public function testExportCreatesOpmlFile()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $group_name = $this->fake('sentence');
        $feed_url = $this->fake('url');
        $feed_site_url = $this->fake('url');
        $group_id = $this->create('group', [
            'name' => $group_name,
            'user_id' => $user_id,
        ]);
        $collection_1_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection_2_id = $this->create('collection', [
            'type' => 'feed',
            'is_public' => true,
            'feed_url' => $feed_url,
            'feed_site_url' => $feed_site_url,
        ]);
        $collection_3_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => true,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user_id,
            'collection_id' => $collection_1_id,
            'time_filter' => 'all',
        ]);
        $this->create('followed_collection', [
            'user_id' => $user_id,
            'collection_id' => $collection_2_id,
            'time_filter' => 'strict',
        ]);
        $this->create('followed_collection', [
            'user_id' => $user_id,
            'collection_id' => $collection_3_id,
            'group_id' => $group_id,
        ]);

        $filepath = $data_exporter->export($user_id);

        $opml_content = $this->zipGetContents($filepath, 'followed.opml.xml');
        $opml = \SpiderBits\Opml::fromText($opml_content);
        $this->assertSame(3, count($opml->outlines));

        $collection_1_url_feed = \Minz\Url::absoluteFor('collection feed', ['id' => $collection_1_id]);
        $collection_1_url = \Minz\Url::absoluteFor('collection', ['id' => $collection_1_id]);
        $this->assertSame($collection_1_url_feed, $opml->outlines[0]['xmlUrl']);
        $this->assertSame($collection_1_url, $opml->outlines[0]['htmlUrl']);
        $this->assertSame('/flusio/filters/all', $opml->outlines[0]['category']);

        $this->assertSame($feed_url, $opml->outlines[1]['xmlUrl']);
        $this->assertSame($feed_site_url, $opml->outlines[1]['htmlUrl']);
        $this->assertSame('/flusio/filters/strict', $opml->outlines[1]['category']);

        $this->assertSame($group_name, $opml->outlines[2]['text']);
        $this->assertSame(1, count($opml->outlines[2]['outlines']));

        $collection_3_url_feed = \Minz\Url::absoluteFor('collection feed', ['id' => $collection_3_id]);
        $collection_3_url = \Minz\Url::absoluteFor('collection', ['id' => $collection_3_id]);
        $group_outlines = $opml->outlines[2]['outlines'];
        $this->assertSame($collection_3_url_feed, $group_outlines[0]['xmlUrl']);
        $this->assertSame($collection_3_url, $group_outlines[0]['htmlUrl']);
    }

    public function testExportCreatesBookmarksFile()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $bookmarks = $user->bookmarks();
        $link_url = $this->fake('url');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $bookmarks->id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $filepath = $data_exporter->export($user_id);

        $feed_content = $this->zipGetContents($filepath, 'bookmarks.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('flusio:type:bookmarks', $feed->categories['flusio:type:bookmarks']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertSame($published_at->getTimestamp(), $entry->published_at->getTimestamp());
    }

    public function testExportCreatesNewsFile()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $news = $user->news();
        $link_url = $this->fake('url');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $news->id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $filepath = $data_exporter->export($user_id);

        $feed_content = $this->zipGetContents($filepath, 'news.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('flusio:type:news', $feed->categories['flusio:type:news']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertSame($published_at->getTimestamp(), $entry->published_at->getTimestamp());
    }

    public function testExportCreatesReadFile()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $read_list = $user->readList();
        $link_url = $this->fake('url');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $read_list->id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $filepath = $data_exporter->export($user_id);

        $feed_content = $this->zipGetContents($filepath, 'read.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('flusio:type:read', $feed->categories['flusio:type:read']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertSame($published_at->getTimestamp(), $entry->published_at->getTimestamp());
    }

    public function testExportCreatesNeverFile()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $never_list = $user->neverList();
        $link_url = $this->fake('url');
        $published_at = $this->fake('dateTime');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $never_list->id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $filepath = $data_exporter->export($user_id);

        $feed_content = $this->zipGetContents($filepath, 'never.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('flusio:type:never', $feed->categories['flusio:type:never']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertSame($published_at->getTimestamp(), $entry->published_at->getTimestamp());
    }

    public function testExportCreatesCollectionsFiles()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $topic_label = $this->fake('word');
        $group_name = $this->fake('word');
        $collection_name = $this->fake('sentence');
        $collection_description = $this->fake('sentence');
        $link_url = $this->fake('url');
        $published_at = $this->fake('dateTime');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $group_id = $this->create('group', [
            'user_id' => $user_id,
            'name' => $group_name,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'name' => $collection_name,
            'description' => $collection_description,
            'is_public' => true,
            'group_id' => $group_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
            'is_hidden' => true,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->create('collection_to_topic', [
            'topic_id' => $topic_id,
            'collection_id' => $collection_id,
        ]);

        $filepath = $data_exporter->export($user_id);

        $feed_content = $this->zipGetContents($filepath, "collections/{$collection_id}.atom.xml");
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame($collection_name, $feed->title);
        $this->assertSame($collection_description, $feed->description);
        $this->assertSame(\Minz\Url::absoluteFor('collection', ['id' => $collection_id]), $feed->link);
        $this->assertSame(4, count($feed->categories));
        $this->assertSame($topic_label, $feed->categories[$topic_label]);
        $this->assertSame('flusio:public', $feed->categories['flusio:public']);
        $this->assertSame('flusio:type:collection', $feed->categories['flusio:type:collection']);
        $this->assertSame($group_name, $feed->categories['flusio:group']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertSame($published_at->getTimestamp(), $entry->published_at->getTimestamp());
        $this->assertSame(1, count($entry->categories));
        $this->assertSame('flusio:hidden', $entry->categories['flusio:hidden']);
    }

    public function testExportCreatesMessagesFiles()
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = $this->create('user');
        $link_url = $this->fake('url');
        $message_content = $this->fake('paragraph');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $link_url,
            'is_hidden' => true,
        ]);
        $message_id = $this->create('message', [
            'user_id' => $user_id,
            'link_id' => $link_id,
            'content' => $message_content,
        ]);

        $filepath = $data_exporter->export($user_id);

        $feed_content = $this->zipGetContents($filepath, "messages/{$link_id}.atom.xml");
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(\Minz\Url::absoluteFor('link', ['id' => $link_id]), $feed->link);
        $this->assertSame($link_url, $feed->links['via']);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('flusio:hidden', $feed->categories['flusio:hidden']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame(\Minz\Url::absoluteFor('link', ['id' => $link_id]) . "#message-{$message_id}", $entry->link);
        $this->assertSame($message_content, trim($entry->content));
    }

    public function testExportFailsIfUserDoesNotExist()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The user does not exist');

        $data_exporter = new DataExporter($this->exportations_path);
        $user_id = utils\Random::hex(32);

        $data_exporter->export($user_id);
    }

    private function zipGetContents($zip_filepath, $filename)
    {
        $zip_archive = new \ZipArchive();
        $zip_archive->open($zip_filepath);
        $zip_archive->extractTo($this->exportations_path);
        $filepath = $this->exportations_path . '/' . $filename;

        $this->assertTrue(file_exists($filepath), "File {$filename} does not exist in the ZIP archive.");

        return @file_get_contents($filepath);
    }
}
