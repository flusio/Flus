<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionToTopicFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\TopicFactory;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $topic->id,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/topics/{$topic->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $topic_label);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponsePointer($response, 'topics/show.phtml');
    }

    public function testShowDoesNotListCollectionsIfEmpty()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $topic->id,
        ]);

        $response = $this->appRun('GET', "/topics/{$topic->id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotListCollectionsIfOnlyHiddenLinks()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $topic->id,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/topics/{$topic->id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotListCollectionsIfPrivate()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => false,
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $topic->id,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/topics/{$topic->id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowRedirectsIfPageIsOutOfBound()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $topic->id,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/topics/{$topic->id}", [
            'page' => 0,
        ]);

        $this->assertResponseCode($response, 302, "/topics/{$topic->id}?page=1");
    }

    public function testShowFailsIfTopicDoesNotExist()
    {
        $response = $this->appRun('GET', '/topics/not-an-id');

        $this->assertResponseCode($response, 404);
    }
}
