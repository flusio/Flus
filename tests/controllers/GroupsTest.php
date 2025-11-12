<?php

namespace App\controllers;

use App\forms;
use App\models;
use tests\factories\GroupFactory;
use tests\factories\UserFactory;

class GroupsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);

        $response = $this->appRun('GET', "/groups/{$group->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, $group_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);

        $response = $this->appRun('GET', "/groups/{$group->id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fgroups%2F{$group->id}%2Fedit");
    }

    public function testEditFailsIfGroupIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $group = GroupFactory::create([
            'user_id' => $other_user->id,
            'name' => $group_name,
        ]);

        $response = $this->appRun('GET', "/groups/{$group->id}/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testEditFailsIfGroupDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);

        $response = $this->appRun('GET', '/groups/not-an-id/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesGroupName(): void
    {
        $user = $this->login();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $group = $group->reload();
        $this->assertSame($new_group_name, $group->name);
    }

    public function testUpdateRedirectsToFrom(): void
    {
        $user = $this->login();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 302, "/groups/{$group->id}/edit");
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fgroups%2F{$group->id}%2Fedit");
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfGroupIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $other_user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 403);
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfGroupDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', '/groups/not-an-id/edit', [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => 'not the token',
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfNameIsInvalid(): void
    {
        $user = $this->login();
        $name_max_length = models\Group::NAME_MAX_LENGTH;
        $name_length = $name_max_length + 1;
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('regexify', "\w{{$name_length}}");
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, "The name must be less than {$name_max_length} characters");
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, 'The name is required');
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testUpdateFailsIfNameIsAlreadyUsed(): void
    {
        $user = $this->login();
        /** @var string */
        $old_group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $new_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $old_group_name,
        ]);
        GroupFactory::create([
            'user_id' => $user->id,
            'name' => $new_group_name,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\groups\Group::class),
            'name' => $new_group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'groups/edit.phtml');
        $this->assertResponseContains($response, 'You already have a group with this name');
        $group = $group->reload();
        $this->assertSame($old_group_name, $group->name);
    }

    public function testDeleteRemovesGroup(): void
    {
        $user = $this->login();
        $group = GroupFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\groups\DeleteGroup::class),
        ]);

        $this->assertFalse(models\Group::exists($group->id));
    }

    public function testDeleteRedirectsToFrom(): void
    {
        $user = $this->login();
        $group = GroupFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\groups\DeleteGroup::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $group = GroupFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\groups\DeleteGroup::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Group::exists($group->id));
    }

    public function testDeleteFailsIfGroupIsInaccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $group = GroupFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\groups\DeleteGroup::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue(models\Group::exists($group->id));
    }

    public function testDeleteFailsIfGroupDoesNotExist(): void
    {
        $user = $this->login();
        $group = GroupFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', '/groups/not-an-id/delete', [
            'csrf_token' => $this->csrfToken(forms\groups\DeleteGroup::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Group::exists($group->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $group = GroupFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/groups/{$group->id}/delete", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue(models\Group::exists($group->id));
    }
}
