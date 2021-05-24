<?php

namespace flusio\controllers;

use flusio\models;

class GroupsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('get', "/groups/{$group_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, $group_name);
    }

    public function testEditRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $group_name = $this->fake('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user_id,
            'name' => $group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('get', "/groups/{$group_id}/edit", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testEditFailsIfGroupIsInaccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fake('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $other_user_id,
            'name' => $group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('get', "/groups/{$group_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfGroupDoesNotExist()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('get', '/groups/not-an-id/edit', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesGroupName()
    {
        $user = $this->login();
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $group = models\Group::find($group_id);
        $this->assertSame($new_group_name, $group->name);
    }

    public function testUpdateRedirectsToFrom()
    {
        $user = $this->login();
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 302, $from);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user_id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => 'a token',
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfGroupIsInaccessible()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $other_user_id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfGroupDoesNotExist()
    {
        $user = $this->login();
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', '/groups/not-an-id/edit', [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => 'not the token',
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfNameIsInvalid()
    {
        $user = $this->login();
        $name_max_length = models\Group::NAME_MAX_LENGTH;
        $name_length = $name_max_length + 1;
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('regexify', "\w{{$name_length}}");
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, "The name must be less than {$name_max_length} characters");
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfNameIsMissing()
    {
        $user = $this->login();
        $old_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, 'The name is required');
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfNameIsAlreadyUsed()
    {
        $user = $this->login();
        $old_group_name = $this->fakeUnique('text', 50);
        $new_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        $this->create('group', [
            'user_id' => $user->id,
            'name' => $new_group_name,
        ]);
        $from = \Minz\Url::for('collections');

        $response = $this->appRun('post', "/groups/{$group_id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, 'You already have a group with this name');
        $group = models\Group::find($group_id);
        $this->assertSame($old_group_name, $group->name);
    }
}
