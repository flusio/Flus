<?php

namespace flusio\controllers;

use flusio\models;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);
        $link_id = $this->create('link', [
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/topics/{$topic_id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $topic_label);
        $this->assertResponseContains($response, $collection_name);
        $this->assertPointer($response, 'topics/show.phtml');
    }

    public function testShowDoesNotListCollectionsIfEmpty()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);

        $response = $this->appRun('get', "/topics/{$topic_id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotListCollectionsIfOnlyHiddenLinks()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);
        $link_id = $this->create('link', [
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/topics/{$topic_id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotListCollectionsIfPrivate()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);
        $link_id = $this->create('link', [
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/topics/{$topic_id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowRedirectsIfPageIsOutOfBound()
    {
        $topic_label = $this->fakeUnique('sentence');
        $collection_name = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);
        $link_id = $this->create('link', [
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/topics/{$topic_id}", [
            'page' => 0,
        ]);

        $this->assertResponseCode($response, 302, "/topics/{$topic_id}?page=1");
    }

    public function testShowFailsIfTopicDoesNotExist()
    {
        $response = $this->appRun('get', '/topics/not-an-id');

        $this->assertResponseCode($response, 404);
    }
}
