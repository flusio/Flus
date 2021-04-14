<?php

namespace flusio\controllers\news;

use flusio\models;

class PreferencesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $preferences = models\NewsPreferences::init(666, true, true, true);
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('get', '/news/preferences');

        $this->assertResponse($response, 200, 666);
        $this->assertPointer($response, 'news/preferences/show.phtml');
    }

    public function testShowRedirectsIfNotConnected()
    {
        $preferences = models\NewsPreferences::init(666, true, true, true);
        $user_id = $this->create('user', [
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('get', '/news/preferences');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews%2Fpreferences');
    }

    public function testUpdateSavesUserNewsPreferencesAndRedirects()
    {
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => $user->csrf,
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 302, '/news');
        $user = models\User::find($user->id);
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($new_duration, $news_preferences->duration);
        $this->assertSame($new_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($new_from_followed, $news_preferences->from_followed);
        $this->assertSame($new_from_topics, $news_preferences->from_topics);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => 'a token',
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fnews%2Fpreferences');
        $user = models\User::find($user_id);
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => 'not the token',
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $user = models\User::find($user->id);
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testUpdateFailsIfDurationIsInvalid()
    {
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('randomNumber') * -1;
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = true;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = true;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = true;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => $user->csrf,
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 400, "The duration must be between {$min_duration} and {$max_duration}");
        $user = models\User::find($user->id);
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }

    public function testUpdateFailsIfNoFromIsSelected()
    {
        $min_duration = models\NewsPreferences::MIN_DURATION;
        $max_duration = models\NewsPreferences::MAX_DURATION;
        $old_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $new_duration = $this->fake('numberBetween', $min_duration, $max_duration);
        $old_from_bookmarks = $this->fake('boolean');
        $new_from_bookmarks = false;
        $old_from_followed = $this->fake('boolean');
        $new_from_followed = false;
        $old_from_topics = $this->fake('boolean');
        $new_from_topics = false;
        $preferences = models\NewsPreferences::init(
            $old_duration,
            $old_from_bookmarks,
            $old_from_followed,
            $old_from_topics
        );
        $user = $this->login([
            'news_preferences' => $preferences->toJson(),
        ]);

        $response = $this->appRun('post', '/news/preferences', [
            'csrf' => $user->csrf,
            'duration' => $new_duration,
            'from_bookmarks' => $new_from_bookmarks,
            'from_followed' => $new_from_followed,
            'from_topics' => $new_from_topics,
        ]);

        $this->assertResponse($response, 400, 'You must select at least one option');
        $user = models\User::find($user->id);
        $news_preferences = models\NewsPreferences::fromJson($user->news_preferences);
        $this->assertSame($old_duration, $news_preferences->duration);
        $this->assertSame($old_from_bookmarks, $news_preferences->from_bookmarks);
        $this->assertSame($old_from_followed, $news_preferences->from_followed);
        $this->assertSame($old_from_topics, $news_preferences->from_topics);
    }
}
