<?php

namespace App\controllers\profiles;

use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class StreamsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
            'name' => $stream_name,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/streams");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/streams/index.html.twig');
        $this->assertResponseContains($response, $stream_name);
    }

    public function testIndexDoesNotDisplayPrivateStreams(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'is_public' => false,
            'name' => $stream_name,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/streams");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/streams/index.html.twig');
        $this->assertResponseNotContains($response, $stream_name);
    }

    public function testIndexFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id/streams');

        $this->assertResponseCode($response, 404);
    }
}
