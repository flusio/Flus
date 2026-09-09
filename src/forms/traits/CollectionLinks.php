<?php

namespace App\forms\traits;

use App\auth;
use App\models;
use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CollectionLinks
{
    use FromAware;

    #[Form\Field(format: 'Y-m-d')]
    public ?\DateTimeImmutable $date = null;

    #[Form\Field]
    public string $source = '';

    /**
     * @return models\Link[]
     */
    public function links(bool $obtain_links = true): array
    {
        $user = $this->optionAs('user', models\User::class);
        $collection = $this->optionAs('collection', models\Collection::class);

        $options = [];
        if ($this->date) {
            $options['published_date'] = $this->date;
        }
        if ($this->source) {
            $options['source'] = $this->source;
        }

        $options['hidden'] = auth\Access::can($user, 'viewHiddenLinks', $collection);

        $links = $collection->links(options: $options);

        if ($obtain_links) {
            $links = $user->obtainLinks($links);

            $links_to_create = [];

            foreach ($links as $link) {
                if (!$link->isPersisted()) {
                    $link->created_at = \Minz\Time::now();
                    $link->setOrigin($this->from);
                    $links_to_create[] = $link;
                }
            }

            models\Link::bulkInsert($links_to_create);
        }

        return $links;
    }
}
