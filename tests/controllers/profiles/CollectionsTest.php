<?php

namespace App\controllers\profiles;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testShowDisplaysAnEditButtonIfConnectedToItsOwnPage(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseContains($response, 'Edit');
    }

    public function testShowDoesNotDisplayPublicCollectionsWithOnlyHiddenLinks(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotCountHiddenLinksForVisitors(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $visible_link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $hidden_link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $hidden_link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        // Don't sync the publication frequency, or it would display "3 links
        // per day" and the assertions on the number of links would be
        // ambiguous.
        $collection->addLinks(
            [$visible_link, $hidden_link1, $hidden_link2],
            sync_publication_frequency: false,
        );

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '1 link');
        $this->assertResponseNotContains($response, '3 links');
    }

    public function testShowDoesNotCountHiddenLinksForOtherUsers(): void
    {
        $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $visible_link = LinkFactory::create([
            'user_id' => $owner->id,
            'is_hidden' => false,
        ]);
        $hidden_link1 = LinkFactory::create([
            'user_id' => $owner->id,
            'is_hidden' => true,
        ]);
        $hidden_link2 = LinkFactory::create([
            'user_id' => $owner->id,
            'is_hidden' => true,
        ]);
        // Don't sync the publication frequency, or it would display "3 links
        // per day" and the assertions on the number of links would be
        // ambiguous.
        $collection->addLinks(
            [$visible_link, $hidden_link1, $hidden_link2],
            sync_publication_frequency: false,
        );

        $response = $this->appRun('GET', "/p/{$owner->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '1 link');
        $this->assertResponseNotContains($response, '3 links');
    }

    public function testShowCountsHiddenLinksForUsersTheCollectionIsSharedWith(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $visible_link = LinkFactory::create([
            'user_id' => $owner->id,
            'is_hidden' => false,
        ]);
        $hidden_link1 = LinkFactory::create([
            'user_id' => $owner->id,
            'is_hidden' => true,
        ]);
        $hidden_link2 = LinkFactory::create([
            'user_id' => $owner->id,
            'is_hidden' => true,
        ]);
        // Don't sync the publication frequency, or it would display "3 links
        // per day" and the assertions on the number of links would be
        // ambiguous.
        $collection->addLinks(
            [$visible_link, $hidden_link1, $hidden_link2],
            sync_publication_frequency: false,
        );
        $collection->shareWith($user, 'read');

        $response = $this->appRun('GET', "/p/{$owner->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '3 links');
    }

    public function testShowCountsHiddenLinksForTheOwner(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $visible_link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $hidden_link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $hidden_link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        // Don't sync the publication frequency, or it would display "3 links
        // per day" and the assertions on the number of links would be
        // ambiguous.
        $collection->addLinks(
            [$visible_link, $hidden_link1, $hidden_link2],
            sync_publication_frequency: false,
        );

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '3 links');
    }

    public function testShowDoesNotDisplayPrivateCollectionsWithLinks(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => false,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id/collections');

        $this->assertResponseCode($response, 404);
    }
}
