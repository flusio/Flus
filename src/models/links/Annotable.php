<?php

namespace App\models\links;

use App\models\Note;

/**
 * Add the notes that a user can attach to a link.
 *
 * This trait requires utils\Memoizer and links\Taggable.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Annotable
{
    /**
     * Return the notes attached to the current link
     *
     * @return Note[]
     */
    public function notes(): array
    {
        return $this->memoize('notes', function (): array {
            return Note::listByLink($this);
        });
    }

    /**
     * Set the notes without querying the database.
     *
     * @param Note[] $notes
     *
     * @see Preloader
     */
    public function preloadNotes(array $notes): void
    {
        $this->memoizeValue('notes', $notes);
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
     * Attach a new note to the link.
     */
    public function addNote(Note $note): void
    {
        $note->link_id = $this->id;
        $note->save();

        $this->unmemoizeNotes();
        $this->refreshTags();
    }

    /**
     * Update the note of the link.
     */
    public function updateNote(Note $note): void
    {
        $note->save();

        $this->unmemoizeNotes();
        $this->refreshTags();
    }

    /**
     * Detach a note from the link.
     */
    public function removeNote(Note $note): void
    {
        $note->remove();

        $this->unmemoizeNotes();
        $this->refreshTags();
    }

    /**
     * Return the number of notes attached to the current link
     */
    public function numberNotes(): int
    {
        return $this->memoize('number_notes', function (): int {
            return Note::countBy([
                'link_id' => $this->id,
            ]);
        });
    }

    /**
     * Set the number of notes without querying the database.
     *
     * @see Preloader
     */
    public function preloadNumberNotes(int $number): void
    {
        $this->memoizeValue('number_notes', $number);
    }

    /**
     * Forget the memoized notes of the link.
     *
     * It must be called after a note has been created or deleted, otherwise
     * the link would keep returning the notes as they were before.
     */
    private function unmemoizeNotes(): void
    {
        $this->unmemoize('notes');
        $this->unmemoize('number_notes');
    }
}
