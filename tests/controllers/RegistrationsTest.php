<?php

namespace flusio\controllers;

use flusio\auth;
use flusio\models;

class RegistrationsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\MailerAsserts;

    public function testNewRendersCorrectly()
    {
        $response = $this->appRun('get', '/registration');

        $this->assertResponse($response, 200);
    }

    public function testNewRedirectsToHomeIfConnected()
    {
        $this->login();

        $response = $this->appRun('get', '/registration');

        $this->assertResponse($response, 302, '/');
    }

    public function testNewRedirectsToLoginIfRegistrationsAreClosed()
    {
        \Minz\Configuration::$application['registrations_opened'] = false;

        $response = $this->appRun('get', '/registration');

        \Minz\Configuration::$application['registrations_opened'] = true;
        $this->assertResponse($response, 302, '/login');
    }

    public function testCreateCreatesAUserAndRedirects()
    {
        $this->assertSame(0, models\User::count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\User::count());
        $this->assertResponse($response, 302, '/onboarding');
    }

    public function testCreateCreatesARegistrationValidationToken()
    {
        $this->freeze($this->fake('dateTime'));

        $this->assertSame(0, models\Token::count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        // it also creates a session token
        $this->assertSame(2, models\Token::count());

        $user = models\User::take();
        $token = models\Token::findBy(['token' => $user->validation_token]);
        $this->assertEquals(\Minz\Time::fromNow(1, 'day'), $token->expired_at);
    }

    public function testCreateSendsAValidationEmail()
    {
        $email = $this->fake('email');

        $this->assertEmailsCount(0);

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $this->assertEmailsCount(1);

        $token = models\Token::take();
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertEmailSubject($email_sent, '[flusio] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
    }

    public function testCreateLogsTheUserIn()
    {
        $email = $this->fake('email');

        $user = auth\CurrentUser::get();
        $this->assertNull($user);
        $this->assertSame(0, models\Session::count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $user = auth\CurrentUser::get();
        $this->assertSame($email, $user->email);
        $this->assertSame(1, models\Session::count());
    }

    public function testCreateReturnsACookie()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $session = models\Session::take();
        $cookie = $response->cookies()['flusio_session_token'];
        $this->assertSame($session->token, $cookie['value']);
        $this->assertSame('Lax', $cookie['options']['samesite']);
    }

    public function testCreateTakesAcceptTermsIfExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
            'accept_terms' => true,
        ]);

        @unlink($terms_path);
        $this->assertSame(1, models\User::count());
        $this->assertResponse($response, 302, '/onboarding');
    }

    public function testCreateAddFollowedCollectionsIfDefaultFeedsExist()
    {
        $default_feeds_path = \Minz\Configuration::$data_path . '/default-feeds.opml.xml';
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
        $email = $this->fake('email');

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        @unlink($default_feeds_path);
        $this->assertResponse($response, 302, '/onboarding');
        $user = models\User::findBy(['email' => $email]);
        $feed = models\Collection::findBy([
            'feed_url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
        ]);
        $this->assertNotNull($user);
        $this->assertNotNull($feed);
        $this->assertTrue($user->isFollowing($feed->id));
    }

    public function testCreateImportsBookmarksIfDefaultBookmarksExist()
    {
        $default_bookmarks_path = \Minz\Configuration::$data_path . '/default-bookmarks.atom.xml';
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
        $email = $this->fake('email');

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        @unlink($default_bookmarks_path);
        $this->assertResponse($response, 302, '/onboarding');
        $user = models\User::findBy(['email' => $email]);
        $this->assertNotNull($user);
        $bookmarks = $user->bookmarks();
        $links = $bookmarks->links();
        $this->assertSame(1, count($links));
        $this->assertSame('Bilan 2021', $links[0]->title);
        $this->assertSame('https://flus.fr/carnet/bilan-2021.html', $links[0]->url);
    }

    public function testCreateRedirectsToHomeIfConnected()
    {
        $this->login();

        $this->assertSame(1, models\User::count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\User::count());
        $this->assertResponse($response, 302, '/');
    }

    public function testCreateCreatesDefaultCollections()
    {
        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponse($response, 302, '/onboarding');
        $this->assertGreaterThan(0, models\Collection::count());
        $user = auth\CurrentUser::get();
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

    public function testCreateRedirectsIfRegistrationsAreClosed()
    {
        \Minz\Configuration::$application['registrations_opened'] = false;

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        \Minz\Configuration::$application['registrations_opened'] = true;
        $this->assertResponse($response, 302, '/login');
        $this->assertSame(0, models\User::count());
    }

    public function testCreateFailsIfCsrfIsWrong()
    {
        \Minz\CSRF::generate();

        $response = $this->appRun('post', '/registration', [
            'csrf' => 'not the token',
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'A security verification failed');
    }

    public function testCreateFailsIfUsernameIsMissing()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'The username is required');
    }

    public function testCreateFailsIfUsernameIsTooLong()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('sentence', 50, false),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'The username must be less than 50 characters');
    }

    public function testCreateFailsIfUsernameContainsAnAt()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name') . '@',
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username cannot contain the character ‘@’.');
    }

    public function testCreateFailsIfEmailIsMissing()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'The address email is required');
    }

    public function testCreateFailsIfEmailIsInvalid()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('word'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'The address email is invalid');
    }

    public function testCreateFailsIfEmailAlreadyExistsAndValidated()
    {
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\User::count());
        $this->assertResponse($response, 400, 'An account already exists with this email address');
    }

    public function testCreateFailsIfPasswordIsMissing()
    {
        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
        ]);

        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'The password is required');
    }

    public function testCreateFailsIfAcceptTermsIsFalseAndTermsExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('post', '/registration', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
            'accept_terms' => false,
        ]);

        @unlink($terms_path);
        $this->assertSame(0, models\User::count());
        $this->assertResponse($response, 400, 'You must accept the terms of service');
    }
}
