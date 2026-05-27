<?php

namespace App\forms\api;

use App\models;
use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EmptyJournal extends Form
{
    #[Form\Field(format: 'Y-m-d')]
    public ?\DateTimeImmutable $date = null;

    #[Form\Field]
    public ?string $source = null;

    private models\User $user;

    public function __construct(models\User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    /**
     * @return models\Link[]
     */
    public function links(): array
    {
        $options = [];
        if ($this->date) {
            $options['published_date'] = $this->date;
        }

        $source = $this->normalizedSource();
        if ($source) {
            $options['source'] = $source;
        }

        $news = $this->user->news();
        return $news->links(options: $options);
    }

    /**
     * Return the normalized source.
     *
     * Before Flus 2.5.0, sources followed the format "<source type>#<source id>",
     * where "source type" could either be "user" or "collection". Since Flus
     * 2.5.0, only "collection" source are supported. Thus, the source only
     * contains the id of the collection.
     *
     * This method enables to support both formats by always returning (only)
     * the source id.
     *
     * @deprecated Can be removed in version 3.0.0.
     */
    public function normalizedSource(): ?string
    {
        if (!$this->source) {
            return null;
        }

        if (!str_contains($this->source, '#')) {
            return $this->source;
        }

        list($source_type, $source_id) = explode('#', $this->source, 2);

        if ($source_type !== 'collection') {
            return null;
        }

        return $source_id;
    }
}
