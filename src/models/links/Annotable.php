<?php

namespace App\models\links;

use App\models\Note;
use Minz\Database;

/**
 * Add the notes that a user can attach to a link.
 *
 * This trait requires links\Taggable.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Annotable
{
    #[Database\Column(computed: true)]
    public ?int $number_notes = null;

    /**
     * Return the notes attached to the current link
     *
     * @return Note[]
     */
    public function notes(): array
    {
        return Note::listByLink($this);
    }

    /**
     * Return the notepad, containing the notes grouped by dates
     *
     * @return array<string, Note[]>
     */
    public function notepad(): array
    {
        $notepad = [];

        foreach ($this->notes() as $note) {
            $date_iso = $note->created_at->format('Y-m-d');
            $notepad[$date_iso][] = $note;
        }

        return $notepad;
    }

    /**
     * Return a new note.
     *
     * It is initialized with this link and the link's user. The note is not
     * saved in database yet.
     */
    public function initNote(): Note
    {
        if (!$this->user_id) {
            throw new \Exception("Cannot initialize a note for link #{$this->id} as user is null.");
        }

        return new Note($this->user_id, $this->id);
    }

    public function numberNotes(): int
    {
        if ($this->number_notes !== null) {
            return $this->number_notes;
        } else {
            return Note::countBy([
                'link_id' => $this->id,
            ]);
        }
    }
}
