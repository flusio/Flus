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
        if ($this->source) {
            $options['source'] = $this->source;
        }

        $news = $this->user->news();
        return $news->links(options: $options);
    }
}
