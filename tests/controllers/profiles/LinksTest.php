<?php

namespace App\controllers\profiles;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
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
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
            'title' => $link_title,
        ]);
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/links");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/links/index.html.twig');
        $this->assertResponseContains($response, $link_title);
    }

    public function testShowDisplaysAnEditButtonIfConnectedToItsOwnPage(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', "/p/{$user->id}/links");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/links/index.html.twig');
        $this->assertResponseContains($response, 'Edit');
    }

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id/links');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserIsSupportUser(): void
    {
        $support_user = models\User::supportUser();

        $response = $this->appRun('GET', "/p/{$support_user->id}/links");

        $this->assertResponseCode($response, 404);
    }
}
