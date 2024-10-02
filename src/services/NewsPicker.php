<?php

namespace App\services;

use App\models;

/**
 * The NewsPicker service is a sort of basic artificial intelligence. Its
 * purpose is to select a bunch of links relevant for a given user.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsPicker
{
    private models\User $user;

    public function __construct(models\User $user)
    {
        $this->user = $user;
    }

    /**
     * Pick and return a set of links relevant for news.
     *
     * @return models\Link[]
     */
    public function pick(int $max = 25): array
    {
        return models\Link::listFromFollowedCollections($this->user->id, $max);
    }
}
