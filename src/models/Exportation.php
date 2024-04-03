<?php

namespace App\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'exportations')]
class Exportation
{
    use Database\Recordable;

    public const VALID_STATUSES = ['ongoing', 'finished', 'error'];

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    /** @var value-of<self::VALID_STATUSES> */
    #[Database\Column]
    public string $status;

    #[Database\Column]
    public string $error;

    #[Database\Column]
    public string $filepath;

    #[Database\Column]
    public string $user_id;

    public function __construct(string $user_id)
    {
        $this->status = 'ongoing';
        $this->error = '';
        $this->filepath = '';
        $this->user_id = $user_id;
    }

    /**
     * Stop and mark the exportation as finished
     */
    public function finish(string $filepath): void
    {
        $this->status = 'finished';
        $this->filepath = $filepath;
    }

    /**
     * Stop and mark the exportation as failed
     */
    public function fail(string $error): void
    {
        $this->status = 'error';
        $this->error = $error;
    }
}
