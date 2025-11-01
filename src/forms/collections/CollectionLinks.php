<?php

namespace App\forms\collections;

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
        $user = $this->user();
        $collection = $this->collection();

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

    public function user(): models\User
    {
        $user = $this->options->get('user');

        if (!($user instanceof models\User)) {
            throw new \LogicException('User must be passed as an option of the form.');
        }

        return $user;
    }

    public function collection(): models\Collection
    {
        $collection = $this->options->get('collection');

        if (!($collection instanceof models\Collection)) {
            throw new \LogicException('Collection must be passed as an option of the form.');
        }

        return $collection;
    }

    #[Form\OnHandleRequest]
    public function setFrom(Request $request): void
    {
        $this->from = utils\RequestHelper::from($request);
    }
}
