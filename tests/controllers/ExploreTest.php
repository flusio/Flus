<?php

namespace App\controllers;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\GroupFactory;
use tests\factories\LinkFactory;
use tests\factories\TopicFactory;

class ExploreTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly(): void
    {
        $topic_label = 'My topic';
        $collection_name = 'My collection';
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->setTopics([$topic]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/explore?topic={$topic->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $topic_label);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseTemplateName($response, 'explore/show.html.twig');
    }

    public function testShowDoesNotListCollectionsIfEmpty(): void
    {
        $topic_label = 'My topic';
        $collection_name = 'My collection';
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->setTopics([$topic]);

        $response = $this->appRun('GET', "/explore?topic={$topic->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotListCollectionsIfOnlyHiddenLinks(): void
    {
        $topic_label = 'My topic';
        $collection_name = 'My collection';
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->setTopics([$topic]);
        $link = LinkFactory::create([
            'is_hidden' => true,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/explore?topic={$topic->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotListCollectionsIfPrivate(): void
    {
        $topic_label = 'My topic';
        $collection_name = 'My collection';
        $topic = TopicFactory::create([
            'label' => $topic_label,
        ]);
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $collection->setTopics([$topic]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/explore?topic={$topic->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowRendersPlaceholderIfNotTopics(): void
    {
        $collection_name = 'My collection';
        $collection = CollectionFactory::create([
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'is_hidden' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', '/explore');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Exploration is disabled as administrator did not setup topics.');
        $this->assertResponseTemplateName($response, 'explore/disabled.html.twig');
    }

    public function testDiscoveryRedirectsToShow(): void
    {
        $response = $this->appRun('GET', '/discovery');

        $this->assertResponseCode($response, 302, '/explore');
    }

    public function testTopicRedirectsToShow(): void
    {
        $response = $this->appRun('GET', '/topics/foo');

        $this->assertResponseCode($response, 302, '/explore?topic=foo');
    }
}
