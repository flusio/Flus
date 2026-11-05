<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * Represent a user of Flus.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'users')]
class User
{
    use Database\Recordable;
    use Database\Resource;
    use users\Authenticable;
    use users\Demo;
    use users\EmailValidable;
    use users\HasActivity;
    use users\HasAvatar;
    use users\HasCollections;
    use users\HasFeatureFlags;
    use users\HasFollows;
    use users\HasJournal;
    use users\HasLinks;
    use users\HasMastodonAccount;
    use users\HasPreferences;
    use users\HasReadingStatuses;
    use users\HasStreams;
    use users\Statistics;
    use utils\Memoizer;
    use Validable;

    public const USERNAME_MAX_LENGTH = 50;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public ?string $subscription_account_id;

    #[Database\Column]
    public \DateTimeImmutable $subscription_expired_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The address email is required.'),
    )]
    #[Validable\Email(
        message: new Translatable('The address email is invalid.'),
    )]
    #[Validable\Unique(
        message: new Translatable('An account already exists with this email address.'),
    )]
    public string $email;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The username is required.'),
    )]
    #[Validable\Length(
        max: self::USERNAME_MAX_LENGTH,
        message: new Translatable('The username must be less than {max} characters.'),
    )]
    #[Validable\Format(
        pattern: '/^[^@]*$/',
        message: new Translatable('The username cannot contain the character ‘@’.'),
    )]
    public string $username;

    #[Database\Column]
    public string $biography = '';

    #[Database\Column]
    public ?string $autoload_modal;

    public function __construct()
    {
        $this->id = \Minz\Random::timebased();
        $this->subscription_expired_at = \Minz\Time::fromNow(1, 'month');
        $this->last_activity_at = \Minz\Time::now();
        $this->username = '';
        $this->email = '';
        $this->locale = utils\Locale::currentLocale();
        $this->autoload_modal = '';
    }

    public function setUsername(string $username): void
    {
        $this->username = trim($username);
    }

    public function setEmail(string $email): void
    {
        $this->email = \Minz\Email::sanitize($email);
    }

    public function setBiography(string $biography): void
    {
        $this->biography = trim($biography);
    }

    /**
     * Return the biography as HTML (from Markdown).
     */
    public function biographyAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this);
        return $markdown->text($this->biography);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:users/{$this->id}";
    }
}
