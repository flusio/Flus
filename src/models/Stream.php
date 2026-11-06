<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'streams')]
class Stream
{
    use Database\Recordable;
    use Database\Resource;
    use Validable;
    use media\HasImage;
    use streams\HasLinks;
    use streams\HasSources;
    use streams\HasViews;
    use streams\OwnedByUser;
    use streams\Shareable;
    use utils\Memoizer;

    public const NAME_MAX_LENGTH = 100;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The name is required.'),
    )]
    #[Validable\Length(
        max: self::NAME_MAX_LENGTH,
        message: new Translatable('The name must be less than {max} characters.'),
    )]
    public string $name = '';

    #[Database\Column]
    public string $description = '';

    #[Database\Column]
    public bool $is_public = false;

    #[Database\Column]
    public bool $display_unread_in_sidenav = true;

    public function __construct(User $user)
    {
        $this->id = \Minz\Random::timebased();
        $this->setOwner($user);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * Return the description as HTML (from Markdown).
     */
    public function descriptionAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this->owner());
        return $markdown->text($this->description());
    }

    public function url(): string
    {
        return \Minz\Url::absoluteFor('stream', ['id' => $this->id]);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * If a view is given, the tag URI identifies the feed of this view. The
     * main view is identified by "default" rather than by its id, as it is
     * deleted when it is reset.
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(?View $view = null): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        $tag_uri = "tag:{$host},{$date}:streams/{$this->id}";

        if ($view) {
            $view_id = $view->is_default ? 'default' : $view->id;
            $tag_uri .= "/views/{$view_id}";
        }

        return $tag_uri;
    }
}
