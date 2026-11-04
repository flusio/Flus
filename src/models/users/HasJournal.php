<?php

namespace App\models\users;

use App\models\Journal;

/**
 * Add methods to access the journal of the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasJournal
{
    /**
     * Return the journal of the user (it is created if it doesn't exist yet).
     */
    public function journal(): Journal
    {
        $journal = Journal::findBy([
            'user_id' => $this->id,
        ]);

        if (!$journal) {
            $journal = new Journal($this);
            $journal->save();
        }

        return $journal;
    }

    /**
     * Return whether the user owns the given journal.
     */
    public function isOwningJournal(Journal $journal): bool
    {
        return $journal->user_id === $this->id;
    }
}
