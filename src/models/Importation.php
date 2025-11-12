<?php

namespace App\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'importations')]
class Importation
{
    use Database\Recordable;
    use Database\Resource;

    public const VALID_TYPES = ['pocket', 'opml'];
    public const VALID_STATUSES = ['ongoing', 'finished', 'error'];

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    /** @var value-of<self::VALID_TYPES> */
    #[Database\Column]
    public string $type;

    /** @var value-of<self::VALID_STATUSES> */
    #[Database\Column]
    public string $status;

    /** @var mixed[] */
    #[Database\Column]
    public array $options;

    #[Database\Column]
    public string $error;

    #[Database\Column]
    public string $user_id;

    /**
     * @param value-of<self::VALID_TYPES> $type
     * @param mixed[] $options
     */
    public function __construct(string $type, string $user_id, array $options = [])
    {
        $this->type = $type;
        $this->status = 'ongoing';
        $this->options = $options;
        $this->user_id = $user_id;
        $this->error = '';
    }

    /**
     * Return the owner of the link.
     */
    public function user(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("Link #{$this->id} has invalid user.");
        }

        return $user;
    }

    /**
     * Stop and mark the importation as finished
     */
    public function finish(): void
    {
        $this->status = 'finished';
    }

    /**
     * Stop and mark the importation as failed
     */
    public function fail(string $error): void
    {
        $this->status = 'error';
        $this->error = $error;
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isInError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * @return array{
     *     'import_bookmarks': bool,
     *     'import_favorites': bool,
     * }
     */
    public function pocketOptions(): array
    {
        if ($this->type !== 'pocket') {
            throw new \Exception('This is not a Pocket importation.');
        }

        $clean_options = [
            'import_bookmarks' => true,
            'import_favorites' => true,
        ];

        if (is_bool($this->options['import_bookmarks'])) {
            $clean_options['import_bookmarks'] = $this->options['import_bookmarks'];
        }

        if (is_bool($this->options['import_favorites'])) {
            $clean_options['import_favorites'] = $this->options['import_favorites'];
        }

        return $clean_options;
    }

    public static function findOpmlByUser(User $user): ?self
    {
        return self::findBy([
            'type' => 'opml',
            'user_id' => $user->id,
        ]);
    }
}
