<?php

namespace App\controllers\collections;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class SharesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testIndexRendersExistingShares(): void
    {
        $user = $this->login();
        /** @var string */
        $username = $this->fake('username');
        $other_user = UserFactory::create([
            'username' => $username,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $response = $this->appRun('GET', "/collections/{$collection->id}/share");

        $this->assertResponseContains($response, $username);
    }

    public function testIndexWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'name' => $collection_name,
        ]);
        $collection->shareWith($user, 'write');

        $response = $this->appRun('GET', "/collections/{$collection->id}/share");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fshare");
    }

    public function testIndexFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', '/collections/not-an-id/share');

        $this->assertResponseCode($response, 404);
    }

    public function testIndexFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share");

        $this->assertResponseCode($response, 403);
    }

    public function testIndexFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('GET', "/collections/{$collection->id}/share");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateRendersCorrectly(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
    }

    public function testCreateCreatesCollectionShare(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNotNull($collection_share);
    }

    public function testCreateAcceptsProfileUrlAsUserId(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => \Minz\Url::absoluteFor('profile', ['id' => $other_user->id]),
            'type' => 'read',
        ]);

        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNotNull($collection_share);
    }

    public function testCreateWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);
        $collection->shareWith($user, 'write');

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $yet_another_user->id,
            'type' => 'read',
        ]);

        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $yet_another_user->id,
        ]);
        $this->assertNotNull($collection_share);
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fshare");
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', '/collections/not-an-id/share', [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 404);
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => 'not the token',
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'A security verification failed');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfUserIdIsTheCurrentUserId(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'You can’t share access with the owner of the collection.');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfUserIdDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => 'not a user id',
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'This user doesn’t exist');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => 'not a user id',
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfUserIdIsSupportUserId(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $support_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'This user doesn’t exist');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $support_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCollectionIsAlreadySharedWithUserId(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'The collection is already shared with this user');
    }

    public function testCreateFailsIfTypeIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => 'not a type',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'The type is invalid');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfTypeIsEmpty(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $other_user->id,
            'type' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertResponseContains($response, 'The type is required');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $yet_another_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 403);
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $yet_another_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);
        $collection->shareWith($user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf_token' => $this->csrfToken(forms\collections\ShareCollection::class),
            'user_id' => $yet_another_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 403);
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $yet_another_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testDeleteDeletesCollectionShare(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $this->assertTrue($collection->sharedWith($other_user));

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => $other_user->id,
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertFalse($collection->sharedWith($other_user));
    }

    public function testDeleteWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $collection->shareWith($user, 'write');
        $collection->shareWith($yet_another_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => $yet_another_user->id,
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertFalse($collection->sharedWith($yet_another_user));
    }

    public function testDeleteRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => $other_user->id,
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($collection->sharedWith($other_user));
    }

    public function testDeleteFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $response = $this->appRun('POST', '/collections/not-an-id/unshare', [
            'user_id' => $other_user->id,
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue($collection->sharedWith($other_user));
    }

    public function testDeleteFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $collection->shareWith($yet_another_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => $yet_another_user->id,
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue($collection->sharedWith($yet_another_user));
    }

    public function testDeleteFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $collection->shareWith($user, 'read');
        $collection->shareWith($yet_another_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => $yet_another_user->id,
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue($collection->sharedWith($yet_another_user));
    }

    public function testDeleteFailsIfUserIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => 'not an id',
            'csrf_token' => $this->csrfToken(forms\collections\UnshareCollection::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertSame('This user doesn’t exist.', \Minz\Flash::get('error'));
        $this->assertTrue($collection->sharedWith($other_user));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection->shareWith($other_user, 'read');

        $response = $this->appRun('POST', "/collections/{$collection->id}/unshare", [
            'user_id' => $other_user->id,
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/shares/index.html.twig');
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error'),
        );
        $this->assertTrue($collection->sharedWith($other_user));
    }
}
