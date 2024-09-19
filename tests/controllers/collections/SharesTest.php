<?php

namespace App\controllers\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\UserFactory;

class SharesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
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
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share", [
            'from' => $from,
        ]);

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
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testIndexFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', '/collections/not-an-id/share', [
            'from' => $from,
        ]);

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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testIndexFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/share", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateRendersCorrectly(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
    }

    public function testCreateCreatesCollectionShare(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
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
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
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
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => 'a token',
            'from' => $from,
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', '/collections/not-an-id/share', [
            'csrf' => $user->csrf,
            'from' => $from,
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => 'not the token',
            'from' => $from,
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'You can’t share access with yourself');
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => 'not a user id',
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $support_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
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
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'The collection is already shared with this user');
        $collection_shares = models\CollectionShare::listBy([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $this->assertSame(1, count($collection_shares));
        $this->assertSame($collection_share->id, $collection_shares[0]->id);
    }

    public function testCreateFailsIfTypeIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user->id,
            'type' => 'not a type',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user->id,
            'type' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $yet_another_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 404);
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
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $yet_another_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 404);
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
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $this->assertTrue(models\CollectionShare::exists($collection_share->id));

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertFalse(models\CollectionShare::exists($collection_share->id));
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
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $yet_another_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertFalse(models\CollectionShare::exists($collection_share->id));
    }

    public function testDeleteRendersCorrectly(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
    }

    public function testDeleteRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_from}");
        $this->assertTrue(models\CollectionShare::exists($collection_share->id));
    }

    public function testDeleteFailsIfCollectionShareDoesNotExist(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', '/collections/shares/not-an-id/delete', [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\CollectionShare::exists($collection_share->id));
    }

    public function testDeleteFailsICollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $yet_another_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $yet_another_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\CollectionShare::exists($collection_share->id));
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
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $yet_another_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\CollectionShare::exists($collection_share->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share = CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/shares/{$collection_share->id}/delete", [
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400, $from);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertTrue(models\CollectionShare::exists($collection_share->id));
    }
}
