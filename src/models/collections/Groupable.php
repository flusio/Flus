<?php

namespace App\models\collections;

use App\models\Group;
use Minz\Database;

/**
 * Add the group in which the owner of a collection placed it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Groupable
{
    #[Database\Column]
    public ?string $group_id = null;

    /**
     * Return the group if any.
     */
    public function group(): ?Group
    {
        if (!$this->group_id) {
            return null;
        }

        return Group::require($this->group_id);
    }
}
