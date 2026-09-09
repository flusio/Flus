<?php

namespace App\controllers;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class JournalTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersJournalLinksCorrectly(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        $journal->addLinks([$link]);

        $response = $this->appRun('GET', '/journal');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'journal/index.html.twig');
        $this->assertResponseContains($response, $title);
    }

    public function testIndexRendersIfViaFollowedCollections(): void
    {
        $user = $this->login();
        /** @var string */
        $username = $this->fake('username');
        $other_user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
        $collection_name = $this->fake('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $collection_name,
            'is_public' => true,
        ]);
        $journal = $user->journal();
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'origin' => $origin,
        ]);
        $journal->addLinks([$link]);

        $response = $this->appRun('GET', '/journal');

        $this->assertResponseCode($response, 200);
        $collection_url = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        $collection_anchor = "<a href=\"{$collection_url}\">{$collection_name}</a>";
        $this->assertResponseContains($response, "via <strong>{$collection_anchor}</strong>");
        $profile_url = \Minz\Url::for('profile', ['id' => $other_user->id]);
        $this->assertResponseContains($response, "href=\"{$profile_url}\"");
        $this->assertResponseContains($response, $username);
    }

    public function testIndexRendersIfViaCustomOrigin(): void
    {
        $user = $this->login();
        /** @var string */
        $username = $this->fake('username');
        $other_user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
        $collection_name = $this->fake('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $collection_name,
        ]);
        $journal = $user->journal();
        $origin = 'Internet';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'origin' => $origin,
        ]);
        $journal->addLinks([$link]);

        $response = $this->appRun('GET', '/journal');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "via <strong>{$origin}</strong>");
    }

    public function testIndexRendersTipsIfNoCandidatesFlash(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        \Minz\Flash::set('no_candidates', true);

        $response = $this->appRun('GET', '/journal');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'There are no relevant links to suggest at this time.');
    }

    public function testIndexHidesAddToCollectionsIfUserHasNoCollections(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        $journal->addLinks([$link]);

        $response = $this->appRun('GET', '/journal');

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, 'Add to collections');
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $journal = $user->journal();
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $link = LinkFactory::create([
            'title' => $title,
            'user_id' => $user->id,
        ]);
        $journal->addLinks([$link]);

        $response = $this->appRun('GET', '/journal');

        $redirect_to = urlencode('/journal');
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testCreateSelectsLinksFromFollowed(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        $other_user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        /** @var string */
        $link_url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $created_at);
        $user->follow($collection);

        $response = $this->appRun('POST', '/journal', [
            'csrf_token' => $this->csrfToken(forms\FillJournal::class),
        ]);

        $this->assertResponseCode($response, 302, '/journal');
        $journal_links = $journal->links();
        $this->assertSame(1, count($journal_links));
        $journal_link = $journal_links[0];
        $this->assertNotSame($link->id, $journal_link->id);
        $this->assertSame($link->url, $journal_link->url);
        $this->assertSame($user->id, $journal_link->user_id);
        $this->assertSame($link->title, $journal_link->title);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $collection->id]);
        $this->assertSame($origin, $journal_link->origin);
    }

    public function testCreateGroupsLinksBySourcesGroups(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        $other_user = UserFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link3 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link1, $link2], at: \Minz\Time::ago(1, 'day'));
        $collection->addLinks([$link3], at: \Minz\Time::ago(2, 'days'));
        $user->follow($collection);

        $response = $this->appRun('POST', '/journal', [
            'csrf_token' => $this->csrfToken(forms\FillJournal::class),
        ]);

        $this->assertResponseCode($response, 302, '/journal');
        $journal_links = $journal->links(['published_at']);
        $this->assertSame(3, count($journal_links));
        $this->assertTrue($journal_links[0]->group_by_source);
        $this->assertTrue($journal_links[1]->group_by_source);
        // This one is published a different day, so it's not grouped.
        $this->assertFalse($journal_links[2]->group_by_source);
    }

    public function testCreateDoesNotDuplicatesLink(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        $other_user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        /** @var string */
        $link_url = $this->fake('url');
        $owned_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $link_url,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $created_at);
        $user->follow($collection);

        $response = $this->appRun('POST', '/journal', [
            'csrf_token' => $this->csrfToken(forms\FillJournal::class),
        ]);

        $this->assertResponseCode($response, 302, '/journal');
        $journal_links = $journal->links();
        $this->assertSame(1, count($journal_links));
        $journal_link = $journal_links[0];
        $this->assertSame($owned_link->id, $journal_link->id);
        $this->assertSame($user->id, $journal_link->user_id);
        $this->assertSame($link_url, $journal_link->url);
    }

    public function testCreateSetsFlashIfNoCandidates(): void
    {
        $user = $this->login();

        $response = $this->appRun('POST', '/journal', [
            'csrf_token' => $this->csrfToken(forms\FillJournal::class),
        ]);

        $this->assertTrue(\Minz\Flash::get('no_candidates'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $journal = $user->journal();
        $other_user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        /** @var string */
        $link_url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $created_at);
        $user->follow($collection);

        $response = $this->appRun('POST', '/journal', [
            'csrf_token' => $this->csrfToken(forms\FillJournal::class),
        ]);

        $redirect_to = urlencode('/journal');
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]));
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $journal = $user->journal();
        $other_user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        /** @var string */
        $link_url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection->addLinks([$link], at: $created_at);
        $user->follow($collection);

        $response = $this->appRun('POST', '/journal', [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertFalse(models\Link::existsBy([
            'user_id' => $user->id,
            'url' => $link_url,
        ]));
    }
    public function testNewsRedirectsToJournal(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/news');

        $this->assertResponseCode($response, 301, '/journal');
    }
}
