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
    public ?string $origin = null;

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

        // @deprecated Can be removed in version 3.0.0.
        $source_origin = $this->sourceToOrigin();
        if ($source_origin) {
            $options['origin'] = $source_origin;
        }

        if ($this->origin) {
            $options['origin'] = $this->origin;
        }

        $news = $this->user->news();
        return $news->links(options: $options);
    }

    public function sourceToOrigin(): ?string
    {
        if (!$this->source) {
            return null;
        }

        list($source_type, $source_id) = explode('#', $this->source, 2);

        return match ($source_type) {
            'user' => \Minz\Url::absoluteFor('profile', ['id' => $source_id]),
            'collection' => \Minz\Url::absoluteFor('collection', ['id' => $source_id]),
            default => null,
        };
    }
}
