<?php

namespace App\controllers;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionToTopicFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\TopicFactory;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        /** @var string */
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
        $this->assertResponseTemplateName($response, 'topics/show.phtml');
    }

    public function testShowDoesNotListCollectionsIfEmpty(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testShowDoesNotListCollectionsIfOnlyHiddenLinks(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testShowDoesNotListCollectionsIfPrivate(): void
    {
        /** @var string */
        $topic_label = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testShowFailsIfTopicDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/topics/not-an-id');

        $this->assertResponseCode($response, 404);
    }
}
