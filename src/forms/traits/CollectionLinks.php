<?php

namespace App\forms\traits;

use App\auth;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Request;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CollectionLinks
{
    #[Form\Field(format: 'Y-m-d')]
    public ?\DateTimeImmutable $date = null;

    #[Form\Field]
    public string $source = '';

    private string $from = '';

    /**
     * @return models\Link[]
     */
    public function links(): array
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

        $collection_links = $collection->links(options: $options);
        $links = $user->obtainLinks($collection_links);

        $links_to_create = [];

        foreach ($links as $link) {
            if (!$link->isPersisted()) {
                $link->created_at = \Minz\Time::now();
                $link->setSourceFrom($this->from);
                $links_to_create[] = $link;
            }
        }

        models\Link::bulkInsert($links_to_create);

        return $links;
    }

    #[Form\OnHandleRequest]
    public function setFrom(Request $request): void
    {
        $this->from = utils\RequestHelper::from($request);
    }
}
