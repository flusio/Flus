<?php

namespace App\services;

use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\CollectionToTopicFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\GroupFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\NoteFactory;
use tests\factories\TopicFactory;
use tests\factories\UserFactory;

class DataExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;

    private string $exportations_path;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setRouterToUrl(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function setExportationsPath(): void
    {
        $tmp_path = \App\Configuration::$tmp_path;
        $this->exportations_path = $tmp_path . '/' . md5((string) rand());
        @mkdir($this->exportations_path, 0777, true);
    }

    public function testConstructFailsIfPathDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The path does not exist');

        new DataExporter('not a path');
    }

    public function testConstructFailsIfPathIsNotADirectory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The path is not a directory');

        $tmp_path = \App\Configuration::$tmp_path;
        $path = $tmp_path . '/' . md5((string) rand());
        touch($path);

        new DataExporter($path);
    }

    public function testExportReturnsTheDataFilepath(): void
    {
        $this->freeze();
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();

        $filepath = $data_exporter->export($user->id);

        $datetime = \Minz\Time::now()->format('Y-m-d_H\hi');
        $expected_filepath = $this->exportations_path . "/{$datetime}_{$user->id}_data.zip";
        $this->assertSame($expected_filepath, $filepath);
    }

    public function testExportCreatesTheDataFile(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();

        $filepath = $data_exporter->export($user->id);

        $this->assertTrue(file_exists($filepath));
    }

    public function testExportCreatesMetadata(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();

        $filepath = $data_exporter->export($user->id);

        $metadata_content = $this->zipGetContents($filepath, 'metadata.json');
        $metadata = json_decode($metadata_content, true);
        $this->assertIsArray($metadata);
        $this->assertSame(utils\UserAgent::get(), $metadata['generator']);
    }

    public function testExportCreatesOpmlFile(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        /** @var string */
        $group_name = $this->fake('sentence');
        /** @var string */
        $feed_url = $this->fake('url');
        /** @var string */
        $feed_site_url = $this->fake('url');
        $group = GroupFactory::create([
            'name' => $group_name,
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection_2 = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'feed_url' => $feed_url,
            'feed_site_url' => $feed_site_url,
        ]);
        $collection_3 = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection_1->id,
            'time_filter' => 'all',
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection_2->id,
            'time_filter' => 'strict',
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection_3->id,
            'group_id' => $group->id,
        ]);

        $filepath = $data_exporter->export($user->id);

        $opml_content = $this->zipGetContents($filepath, 'followed.opml.xml');
        $opml = \SpiderBits\Opml::fromText($opml_content);
        $this->assertSame(3, count($opml->outlines));

        $collection_1_url_feed = \Minz\Url::absoluteFor('collection feed', [
            'id' => $collection_1->id,
            'direct' => 'true',
        ]);
        $collection_1_url = \Minz\Url::absoluteFor('collection', ['id' => $collection_1->id]);
        $this->assertSame($collection_1_url_feed, $opml->outlines[0]['xmlUrl']);
        $this->assertSame($collection_1_url, $opml->outlines[0]['htmlUrl']);
        $this->assertSame('/Flus/filters/all', $opml->outlines[0]['category']);

        $this->assertSame($feed_url, $opml->outlines[1]['xmlUrl']);
        $this->assertSame($feed_site_url, $opml->outlines[1]['htmlUrl']);
        $this->assertSame('/Flus/filters/strict', $opml->outlines[1]['category']);

        $this->assertSame($group_name, $opml->outlines[2]['text']);
        $this->assertIsArray($opml->outlines[2]['outlines']);
        $this->assertSame(1, count($opml->outlines[2]['outlines']));

        $collection_3_url_feed = \Minz\Url::absoluteFor('collection feed', [
            'id' => $collection_3->id,
            'direct' => 'true',
        ]);
        $collection_3_url = \Minz\Url::absoluteFor('collection', ['id' => $collection_3->id]);
        $group_outlines = $opml->outlines[2]['outlines'];
        $this->assertIsArray($group_outlines[0]);
        $this->assertSame($collection_3_url_feed, $group_outlines[0]['xmlUrl']);
        $this->assertSame($collection_3_url, $group_outlines[0]['htmlUrl']);
    }

    public function testExportCreatesBookmarksFile(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        $bookmarks = $user->bookmarks();
        /** @var string */
        $link_url = $this->fake('url');
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
            'created_at' => $published_at,
        ]);

        $filepath = $data_exporter->export($user->id);

        $feed_content = $this->zipGetContents($filepath, 'bookmarks.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('Flus:type:bookmarks', $feed->categories['Flus:type:bookmarks']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertEquals($published_at, $entry->published_at);
    }

    public function testExportCreatesNewsFile(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        $news = $user->news();
        /** @var string */
        $link_url = $this->fake('url');
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
            'created_at' => $published_at,
        ]);

        $filepath = $data_exporter->export($user->id);

        $feed_content = $this->zipGetContents($filepath, 'news.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('Flus:type:news', $feed->categories['Flus:type:news']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertEquals($published_at, $entry->published_at);
    }

    public function testExportCreatesReadFile(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        $read_list = $user->readList();
        /** @var string */
        $link_url = $this->fake('url');
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
            'created_at' => $published_at,
        ]);

        $filepath = $data_exporter->export($user->id);

        $feed_content = $this->zipGetContents($filepath, 'read.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('Flus:type:read', $feed->categories['Flus:type:read']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertEquals($published_at, $entry->published_at);
    }

    public function testExportCreatesNeverFile(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        $never_list = $user->neverList();
        /** @var string */
        $link_url = $this->fake('url');
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
            'created_at' => $published_at,
        ]);

        $filepath = $data_exporter->export($user->id);

        $feed_content = $this->zipGetContents($filepath, 'never.atom.xml');
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('Flus:type:never', $feed->categories['Flus:type:never']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertEquals($published_at, $entry->published_at);
    }

    public function testExportCreatesCollectionsFiles(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        /** @var string */
        $topic_label = $this->fake('word');
        /** @var string */
        $group_name = $this->fake('word');
        /** @var string */
        $collection_name = $this->fake('sentence');
        /** @var string */
        $collection_description = $this->fake('sentence');
        /** @var string */
        $link_url = $this->fake('url');
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $collection_name,
            'description' => $collection_description,
            'is_public' => true,
            'group_id' => $group->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at,
        ]);
        CollectionToTopicFactory::create([
            'topic_id' => $topic->id,
            'collection_id' => $collection->id,
        ]);

        $filepath = $data_exporter->export($user->id);

        $feed_content = $this->zipGetContents($filepath, "collections/{$collection->id}.atom.xml");
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame($collection_name, $feed->title);
        $this->assertSame($collection_description, $feed->description);
        $this->assertSame(\Minz\Url::absoluteFor('collection', ['id' => $collection->id]), $feed->link);
        $this->assertSame(4, count($feed->categories));
        $this->assertSame($topic_label, $feed->categories[$topic_label]);
        $this->assertSame('Flus:public', $feed->categories['Flus:public']);
        $this->assertSame('Flus:type:collection', $feed->categories['Flus:type:collection']);
        $this->assertSame($group_name, $feed->categories['Flus:group']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $this->assertSame($link_url, $entry->link);
        $this->assertEquals($published_at, $entry->published_at);
        $this->assertSame(1, count($entry->categories));
        $this->assertSame('Flus:hidden', $entry->categories['Flus:hidden']);
    }

    public function testExportCreatesNotesFiles(): void
    {
        $data_exporter = new DataExporter($this->exportations_path);
        $user = UserFactory::create();
        /** @var string */
        $link_url = $this->fake('url');
        /** @var string */
        $note_content = $this->fake('paragraph');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
            'is_hidden' => true,
        ]);
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
            'content' => $note_content,
        ]);

        $filepath = $data_exporter->export($user->id);

        $feed_content = $this->zipGetContents($filepath, "notes/{$link->id}.atom.xml");
        $feed = \SpiderBits\feeds\Feed::fromText($feed_content);
        $this->assertSame(\Minz\Url::absoluteFor('link', ['id' => $link->id]), $feed->link);
        $this->assertSame($link_url, $feed->links['via']);
        $this->assertSame(1, count($feed->categories));
        $this->assertSame('Flus:hidden', $feed->categories['Flus:hidden']);
        $this->assertSame(1, count($feed->entries));
        $entry = $feed->entries[0];
        $expected_link = \Minz\Url::absoluteFor('link', ['id' => $link->id]) . "#note-{$note->id}";
        $this->assertSame($expected_link, $entry->link);
        $this->assertSame($note_content, trim($entry->content));
    }

    public function testExportFailsIfUserDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The user does not exist');

        $data_exporter = new DataExporter($this->exportations_path);

        $data_exporter->export('not an id');
    }

    private function zipGetContents(string $zip_filepath, string $filename): string
    {
        $zip_archive = new \ZipArchive();
        $zip_archive->open($zip_filepath);
        $zip_archive->extractTo($this->exportations_path);
        $filepath = $this->exportations_path . '/' . $filename;

        $content = @file_get_contents($filepath);

        $this->assertNotFalse($content, "File {$filename} does not exist in the ZIP archive.");

        return $content;
    }
}
