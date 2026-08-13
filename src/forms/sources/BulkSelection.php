<?php

namespace App\forms\sources;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * A base form to apply an action to a selection of sources.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class BulkSelection extends BaseForm
{
    /** @var string[] */
    #[Form\Field]
    public array $source_ids = [];

    /**
     * The token is named after this class and not after the child classes
     * (i.e. `self` and not `static`): the actions are submitted by a single
     * HTML form, which carries a single token for all of them.
     *
     * @return non-empty-string
     */
    public function csrfTokenName(): string
    {
        return self::class;
    }

    /**
     * Return the selected sources.
     *
     * The sources that the user doesn't follow are ignored.
     *
     * @return models\Collection[]
     */
    public function selectedSources(): array
    {
        if (!$this->source_ids) {
            return [];
        }

        $user = $this->optionAs('user', models\User::class);

        $sources = models\Collection::listBy(['id' => $this->source_ids]);
        $follows = models\FollowedCollection::listByUserAndCollections($user, $sources);

        return array_values(array_filter(
            $sources,
            fn (models\Collection $source): bool => isset($follows[$source->id]),
        ));
    }
}
