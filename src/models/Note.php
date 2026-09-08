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
#[Database\Table(name: 'notes')]
class Note
{
    use dao\BulkQueries;
    use Database\Recordable;
    use Database\Resource;
    use Validable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The content is required.'),
    )]
    public string $content;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $user_id;

    public function __construct(User $user, string $content = '')
    {
        $this->id = \Minz\Random::hex(32);
        $this->content = trim($content);
        $this->user_id = $user->id;
    }

    /**
     * Return the author of the note
     */
    public function user(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("Note #{$this->id} has invalid user.");
        }

        return $user;
    }

    /**
     * Return the link of the note
     */
    public function link(): Link
    {
        $link = Link::find($this->link_id);

        if (!$link) {
            throw new \Exception("Note #{$this->id} has invalid link.");
        }

        return $link;
    }

    /**
     * Return the list of tags in the message.
     *
     * @return string[]
     */
    public function tags(): array
    {
        return utils\Tag::extract($this->content);
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
        return "tag:{$host},{$date}:notes/{$this->id}";
    }

    /**
     * Return the content as HTML (from Markdown).
     */
    public function contentAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this->user());
        return $markdown->text($this->content);
    }

    /**
     * Return the link notes, orderer by creation date
     *
     * @return self[]
     */
    public static function listByLink(Link $link): array
    {
        $sql = <<<SQL
             SELECT * FROM notes
             WHERE link_id = ?
             ORDER BY created_at
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$link->id]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the notes attached to the given links, indexed by the ids of
     * these links and ordered by creation date.
     *
     * The links without any note are absent from the returned array.
     *
     * @param Link[] $links
     *
     * @return array<string, self[]>
     */
    public static function listByLinks(array $links): array
    {
        if (!$links) {
            return [];
        }

        $link_ids = array_column($links, 'id');
        $ids_as_question_marks = array_fill(0, count($link_ids), '?');
        $ids_as_question_marks = implode(', ', $ids_as_question_marks);

        $sql = <<<SQL
            SELECT * FROM notes

            WHERE link_id IN ({$ids_as_question_marks})

            ORDER BY created_at
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($link_ids);

        $notes = [];

        foreach (self::fromDatabaseRows($statement->fetchAll()) as $note) {
            $notes[$note->link_id][] = $note;
        }

        return $notes;
    }

    /**
     * Return the numbers of notes attached to the given links, indexed by the
     * ids of these links.
     *
     * The links without any note are absent from the returned array.
     *
     * @param Link[] $links
     *
     * @return array<string, int>
     */
    public static function countByLinks(array $links): array
    {
        if (!$links) {
            return [];
        }

        $link_ids = array_column($links, 'id');
        $ids_as_question_marks = array_fill(0, count($link_ids), '?');
        $ids_as_question_marks = implode(', ', $ids_as_question_marks);

        $sql = <<<SQL
            SELECT link_id, COUNT(*) AS count
            FROM notes

            WHERE link_id IN ({$ids_as_question_marks})

            GROUP BY link_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($link_ids);

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[$row['link_id']] = intval($row['count']);
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toJson(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->format(\DateTime::ATOM),
            'content' => $this->content,
            'html_content' => $this->contentAsHtml(),
            'tags' => $this->tags(),
        ];
    }
}
