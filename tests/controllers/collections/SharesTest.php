<?php

namespace flusio\controllers\collections;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\UserFactory;

class SharesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
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

    public function testIndexRendersExistingShares()
    {
        $user = $this->login();
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

    public function testIndexWorksIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
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

    public function testIndexRedirectsIfNotConnected()
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

    public function testIndexFailsIfCollectionDoesNotExist()
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

    public function testIndexFailsIfCollectionIsNotShared()
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

    public function testIndexFailsIfCollectionIsSharedWithReadAccess()
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

    public function testCreateRendersCorrectly()
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

    public function testCreateCreatesCollectionShare()
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

    public function testCreateAcceptsProfileUrlAsUserId()
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

    public function testCreateWorksIfCollectionIsSharedWithWriteAccess()
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

    public function testCreateRedirectsToLoginIfNotConnected()
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

    public function testCreateFailsIfCollectionDoesNotExist()
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

    public function testCreateFailsIfCsrfIsInvalid()
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

    public function testCreateFailsIfUserIdIsTheCurrentUserId()
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

    public function testCreateFailsIfUserIdDoesNotExist()
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

    public function testCreateFailsIfUserIdIsSupportUserId()
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

    public function testCreateFailsIfCollectionIsAlreadySharedWithUserId()
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

    public function testCreateFailsIfTypeIsInvalid()
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

    public function testCreateFailsIfTypeIsEmpty()
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

    public function testCreateFailsIfCollectionIsNotShared()
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

    public function testCreateFailsIfCollectionIsSharedWithReadAccess()
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

    public function testDeleteDeletesCollectionShare()
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

    public function testDeleteWorksIfCollectionIsSharedWithWriteAccess()
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

    public function testDeleteRendersCorrectly()
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

    public function testDeleteRedirectsToLoginIfNotConnected()
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

    public function testDeleteFailsIfCollectionShareDoesNotExist()
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

    public function testDeleteFailsICollectionIsNotShared()
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

    public function testDeleteFailsIfCollectionIsSharedWithReadAccess()
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

    public function testDeleteFailsIfCsrfIsInvalid()
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
