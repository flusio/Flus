<?php

namespace flusio\controllers\collections;

use flusio\models;

class SharesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/share", [
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
        $other_user_id = $this->create('user', [
            'username' => $username,
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/share", [
            'from' => $from,
        ]);

        $this->assertResponseContains($response, $username);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/share", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testIndexFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', '/collections/not-an-id/share', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateRedirectsToFrom()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 302, $from);
    }

    public function testCreateCreatesCollectionShare()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'read',
        ]);

        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNotNull($collection_share);
    }

    public function testCreateAcceptsProfileUrlAsUserId()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => \Minz\Url::absoluteFor('profile', ['id' => $other_user_id]),
            'type' => 'read',
        ]);

        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNotNull($collection_share);
    }

    public function testCreateRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => 'a token',
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'read',
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', '/collections/not-an-id/share', [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 404);
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => 'not the token',
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfUserIdIsTheCurrentUserId()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'You can’t share access with yourself');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfUserIdDoesNotExist()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => 'not a user id',
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'This user doesn’t exist');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => 'not a user id',
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfUserIdIsSupportUserId()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $support_user->id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'This user doesn’t exist');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $support_user->id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfCollectionIsAlreadySharedWithUserId()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'read',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'The collection is already shared with this user');
        $collection_shares = models\CollectionShare::listBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertSame(1, count($collection_shares));
        $this->assertSame($collection_share_id, $collection_shares[0]->id);
    }

    public function testCreateFailsIfTypeIsInvalid()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => 'not a type',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'The type is invalid');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testCreateFailsIfTypeIsEmpty()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/share", [
            'csrf' => $user->csrf,
            'from' => $from,
            'user_id' => $other_user_id,
            'type' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/shares/index.phtml');
        $this->assertResponseContains($response, 'The type is required');
        $collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $this->assertNull($collection_share);
    }

    public function testDeleteDeletesCollectionShare()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $this->assertTrue(models\CollectionShare::exists($collection_share_id));

        $response = $this->appRun('post', "/collections/shares/{$collection_share_id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertFalse(models\CollectionShare::exists($collection_share_id));
    }

    public function testDeleteRedirectsToFrom()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/shares/{$collection_share_id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
    }

    public function testDeleteRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/shares/{$collection_share_id}/delete", [
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_from}");
        $this->assertTrue(models\CollectionShare::exists($collection_share_id));
    }

    public function testDeleteFailsIfCollectionShareDoesNotExist()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', '/collections/shares/not-an-id/delete', [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\CollectionShare::exists($collection_share_id));
    }

    public function testDeleteFailsIfUnauthorizedToUpdateCollection()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/shares/{$collection_share_id}/delete", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\CollectionShare::exists($collection_share_id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $collection_share_id = $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/shares/{$collection_share_id}/delete", [
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\CollectionShare::exists($collection_share_id));
    }
}
