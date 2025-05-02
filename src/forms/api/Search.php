<?php

namespace App\forms\api;

use App\models;
use App\services;
use Minz\Form;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Search extends Form
{
    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    #[Validable\Presence(message: 'The link is required.')]
    #[Validable\Url(message: 'The link is invalid.')]
    public string $url = '';

    private models\User $user;

    public function __construct(models\User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    public function link(): models\Link
    {
        $existing_link = models\Link::findBy([
            'user_id' => $this->user->id,
            'url_hash' => models\Link::hashUrl($this->url),
        ]);

        if ($existing_link) {
            return $existing_link;
        }

        $link = new models\Link($this->url, $this->user->id);

        $link_fetcher_service = new services\LinkFetcher();
        $link_fetcher_service->fetch($link);

        return $link;
    }
}
