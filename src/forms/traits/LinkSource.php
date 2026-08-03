<?php

namespace App\forms\traits;

use App\auth;
use App\models;
use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait LinkSource
{
    use FromAware;

    #[Form\Field(bind: false)]
    public string $source = '';

    /**
     * Set the origin of a link.
     *
     * The origin is the collection from which the link is displayed if it is
     * known (e.g. in a stream, where the referrer doesn't tell where the link
     * comes from), or the page from which the user comes from otherwise.
     */
    public function setLinkOrigin(models\Link $link): void
    {
        $source = $this->sourceCollection();

        if ($source) {
            $link->source_id = $source->id;
            $link->setOrigin(\Minz\Url::absoluteFor('collection', ['id' => $source->id]));
        } else {
            $link->setOrigin($this->from);
        }
    }

    /**
     * Return the collection corresponding to the source field.
     *
     * It returns null if the source is unknown, or if the user cannot view it.
     */
    private function sourceCollection(): ?models\Collection
    {
        if (!$this->source) {
            return null;
        }

        $user = $this->optionAs('user', models\User::class);

        $source = models\Collection::find($this->source);

        if (!$source || !auth\Access::can($user, 'view', $source)) {
            return null;
        }

        return $source;
    }
}
