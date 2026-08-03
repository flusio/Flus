<?php

namespace App\models\links;

use Minz\Database;

/**
 * Add the tags of a link.
 *
 * The tags are extracted from the notes attached to the link.
 *
 * This trait requires links\Annotable.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Taggable
{
    /** @var string[] */
    #[Database\Column]
    public array $tags = [];

    public function refreshTags(): void
    {
        $tags = [];

        foreach ($this->notes() as $note) {
            $tags = array_merge($tags, $note->tags());
        }

        $this->setTags($tags);
        $this->save();
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {
        $sanitized_tags = [];

        foreach ($tags as $tag) {
            $lower_tag = mb_strtolower($tag);

            if (!isset($sanitized_tags[$lower_tag])) {
                $sanitized_tags[$lower_tag] = $tag;
            }
        }

        $this->tags = $sanitized_tags;
    }
}
