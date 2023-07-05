<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionToTopicFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\TopicFactory;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
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

        $response = $this->appRun('GET', '/discovery');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $topic_label);
        $this->assertResponseContains($response, '1 collection');
        $this->assertResponsePointer($response, 'discovery/show.phtml');
    }

    public function testShowDoesNotCountCollectionIfEmpty(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $topic->id,
        ]);

        $response = $this->appRun('GET', '/discovery');

        $this->assertResponseContains($response, 'No collections');
        $this->assertResponseNotContains($response, '1 collection');
    }

    public function testShowDoesNotCountCollectionIfOnlyHiddenLink(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
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

        $response = $this->appRun('GET', '/discovery');

        $this->assertResponseContains($response, 'No collections');
        $this->assertResponseNotContains($response, '1 collection');
    }

    public function testShowDoesNotCountCollectionIfPrivate(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
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

        $response = $this->appRun('GET', '/discovery');

        $this->assertResponseContains($response, 'No collections');
        $this->assertResponseNotContains($response, '1 collection');
    }
}
