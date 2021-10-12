<?php

namespace flusio\controllers;

use flusio\models;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $topic_label = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
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

        $response = $this->appRun('get', '/discovery');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $topic_label);
        $this->assertResponseContains($response, '1 collection');
        $this->assertPointer($response, 'discovery/show.phtml');
    }

    public function testShowDoesNotCountCollectionIfEmpty()
    {
        $topic_label = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $topic_id,
        ]);

        $response = $this->appRun('get', '/discovery');

        $this->assertResponseContains($response, 'No collections');
        $this->assertResponseNotContains($response, '1 collection');
    }

    public function testShowDoesNotCountCollectionIfOnlyHiddenLink()
    {
        $topic_label = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
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

        $response = $this->appRun('get', '/discovery');

        $this->assertResponseContains($response, 'No collections');
        $this->assertResponseNotContains($response, '1 collection');
    }

    public function testShowDoesNotCountCollectionIfPrivate()
    {
        $topic_label = $this->fakeUnique('sentence');
        $topic_id = $this->create('topic', [
            'label' => $topic_label,
        ]);
        $collection_id = $this->create('collection', [
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

        $response = $this->appRun('get', '/discovery');

        $this->assertResponseContains($response, 'No collections');
        $this->assertResponseNotContains($response, '1 collection');
    }
}
