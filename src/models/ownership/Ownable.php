<?php

namespace App\models\ownership;

use App\models\Owner;
use App\models\SystemOwner;
use Minz\Database;

/**
 * Add the relation between a content and its owner.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Ownable
{
    /**
     * owner_id is nullable as long as the existing contents are not filled by
     * the migration command.
     */
    #[Database\Column]
    public ?string $owner_id = null;

    /**
     * Return the owner of the content, if any.
     */
    public function owner(): ?Owner
    {
        return $this->memoize('owner', function (): ?Owner {
            if (!$this->owner_id) {
                return null;
            }

            if ($this->owner_id === SystemOwner::ID) {
                return SystemOwner::get();
            }

            return Owner::require($this->owner_id);
        });
    }

    /**
     * Set the owner of the content.
     */
    public function setOwner(Owner $owner): void
    {
        $this->owner_id = $owner->id;
        $this->memoizeValue('owner', $owner);
    }
}
