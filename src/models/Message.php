<?php

namespace flusio\models;

use flusio\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'messages')]
class Message
{
    use dao\Message;
    use Database\Recordable;
    use Validable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The message is required.'),
    )]
    public string $content;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $user_id;

    public function __construct(string $user_id, string $link_id, string $content)
    {
        $this->id = \Minz\Random::hex(32);
        $this->content = trim($content);
        $this->link_id = $link_id;
        $this->user_id = $user_id;
    }

    /**
     * Return the author of the message
     */
    public function user(): User
    {
        return User::find($this->user_id);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \Minz\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:messages/{$this->id}";
    }

    /**
     * Return the content as HTML (from Markdown).
     */
    public function contentAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown();
        return $markdown->text($this->content);
    }
}
