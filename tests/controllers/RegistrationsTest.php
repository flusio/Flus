<?php

namespace App\controllers;

use App\auth;
use App\models;
use tests\factories\UserFactory;

class RegistrationsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testNewRendersCorrectly(): void
    {
        $response = $this->appRun('GET', '/registration');

        $this->assertResponseCode($response, 200);
    }

    public function testNewRedirectsToHomeIfConnected(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/registration');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testNewRedirectsToLoginIfRegistrationsAreClosed(): void
    {
        \App\Configuration::$application['registrations_opened'] = false;

        $response = $this->appRun('GET', '/registration');

        \App\Configuration::$application['registrations_opened'] = true;
        $this->assertResponseCode($response, 302, '/login');
    }

    public function testCreateCreatesAUserAndRedirects(): void
    {
        $this->assertSame(0, models\User::count());

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\User::count());
        $this->assertResponseCode($response, 302, '/onboarding');
    }

    public function testCreateCreatesARegistrationValidationToken(): void
    {
        $this->freeze();

        $this->assertSame(0, models\Token::count());

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        // it also creates a session token
        $this->assertSame(2, models\Token::count());

        $user = models\User::take();
        $this->assertNotNull($user);
        $token = models\Token::findBy(['token' => $user->validation_token]);
        $this->assertNotNull($token);
        $this->assertEquals(
            \Minz\Time::fromNow(1, 'day')->getTimestamp(),
            $token->expired_at->getTimestamp()
        );
    }

    public function testCreateSendsAValidationEmail(): void
    {
        /** @var string */
        $email = $this->fake('email');

        $this->assertEmailsCount(0);

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $this->assertEmailsCount(1);

        $token = models\Token::take();
        $this->assertNotNull($token);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertNotNull($email_sent);
        $this->assertEmailSubject($email_sent, '[Flus] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
    }

    public function testCreateLogsTheUserIn(): void
    {
        /** @var string */
        $email = $this->fake('email');

        $user = auth\CurrentUser::get();
        $this->assertNull($user);
        $this->assertSame(0, models\Session::count());

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $user = auth\CurrentUser::get();
        $this->assertNotNull($user);
        $this->assertSame($email, $user->email);
        $this->assertSame(1, models\Session::count());
    }

    public function testCreateReturnsACookie(): void
    {
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $session = models\Session::take();
        $this->assertNotNull($session);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $cookie = $response->cookies()['session_token'];
        $this->assertSame($session->token, $cookie['value']);
        $this->assertSame('Lax', $cookie['options']['samesite']);
    }

    public function testCreateTakesAcceptTermsIfExist(): void
    {
        $app_path = \App\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
            'accept_terms' => true,
        ]);

        @unlink($terms_path);
        $this->assertSame(1, models\User::count());
        $this->assertResponseCode($response, 302, '/onboarding');
    }

    public function testCreateAddFollowedCollectionsIfDefaultFeedsExist(): void
    {
        $default_feeds_path = \App\Configuration::$data_path . '/default-feeds.opml.xml';
        file_put_contents($default_feeds_path, <<<OPML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0">
                <head></head>
                <body>
                    <outline
                        type="rss"
                        text="Carnet de Flus"
                        xmlUrl="https://flus.fr/carnet/feeds/all.atom.xml"
                        htmlUrl="https://flus.fr/carnet/"
                    />
                </body>
            </opml>
            OPML
        );
        /** @var string */
        $email = $this->fake('email');

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        @unlink($default_feeds_path);
        $this->assertResponseCode($response, 302, '/onboarding');
        $user = models\User::findBy(['email' => $email]);
        $feed = models\Collection::findBy([
            'feed_url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
        ]);
        $this->assertNotNull($user);
        $this->assertNotNull($feed);
        $this->assertTrue($user->isFollowing($feed->id));
    }

    public function testCreateImportsBookmarksIfDefaultBookmarksExist(): void
    {
        $default_bookmarks_path = \App\Configuration::$data_path . '/default-bookmarks.atom.xml';
        file_put_contents($default_bookmarks_path, <<<TXT
            <?xml version="1.0" encoding="UTF-8"?>
            <feed xmlns="http://www.w3.org/2005/Atom">
              <title>Bookmarks</title>
              <entry>
                <title>Bilan 2021</title>
                <id>tag:localhost,2022-01-28:links/1723190948473041017</id>
                <link href="https://flus.fr/carnet/bilan-2021.html" rel="alternate" type="text/html"/>
                <published>2022-01-28T09:42:30+00:00</published>
                <updated>2022-01-28T09:42:30+00:00</updated>
              </entry>
            </feed>
            TXT
        );
        /** @var string */
        $email = $this->fake('email');

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        @unlink($default_bookmarks_path);
        $this->assertResponseCode($response, 302, '/onboarding');
        $user = models\User::findBy(['email' => $email]);
        $this->assertNotNull($user);
        $bookmarks = $user->bookmarks();
        $links = $bookmarks->links();
        $this->assertSame(1, count($links));
        $this->assertSame('Bilan 2021', $links[0]->title);
        $this->assertSame('https://flus.fr/carnet/bilan-2021.html', $links[0]->url);
    }

    public function testCreateRedirectsToHomeIfConnected(): void
    {
        $this->login();

        $this->assertSame(1, models\User::count());

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\User::count());
        $this->assertResponseCode($response, 302, '/');
    }

    public function testCreateCreatesDefaultCollections(): void
    {
        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponseCode($response, 302, '/onboarding');
        $this->assertGreaterThan(0, models\Collection::count());
        $user = auth\CurrentUser::get();
        $this->assertNotNull($user);
        $bookmarks = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'news',
        ]);
        $read_list = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $never_list = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'never',
        ]);
        $this->assertNotNull($bookmarks);
        $this->assertNotNull($news);
        $this->assertNotNull($read_list);
        $this->assertNotNull($never_list);
    }

    public function testCreateRedirectsIfRegistrationsAreClosed(): void
    {
        \App\Configuration::$application['registrations_opened'] = false;

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        \App\Configuration::$application['registrations_opened'] = true;
        $this->assertResponseCode($response, 302, '/login');
        $this->assertSame(0, models\User::count());
    }

    public function testCreateFailsIfCsrfIsWrong(): void
    {
        \Minz\Csrf::generate();

        $response = $this->appRun('POST', '/registration', [
            'csrf' => 'not the token',
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
    }

    public function testCreateFailsIfUsernameIsMissing(): void
    {
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username is required');
    }

    public function testCreateFailsIfUsernameIsTooLong(): void
    {
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('sentence', 50, false),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username must be less than 50 characters');
    }

    public function testCreateFailsIfUsernameContainsAnAt(): void
    {
        /** @var string */
        $username = $this->fake('name');
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $username . '@',
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username cannot contain the character ‘@’.');
    }

    public function testCreateFailsIfEmailIsMissing(): void
    {
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is required');
    }

    public function testCreateFailsIfEmailIsInvalid(): void
    {
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('word'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is invalid');
    }

    public function testCreateFailsIfEmailAlreadyExistsAndValidated(): void
    {
        /** @var string */
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'An account already exists with this email address');
    }

    public function testCreateFailsIfPasswordIsMissing(): void
    {
        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The password is required');
    }

    public function testCreateFailsIfAcceptTermsIsFalseAndTermsExist(): void
    {
        $app_path = \App\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('POST', '/registration', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
            'accept_terms' => false,
        ]);

        @unlink($terms_path);
        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You must accept the terms of service');
    }
}
