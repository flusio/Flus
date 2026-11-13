<?php

namespace App\models;

use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ReadList extends ReadingStatusList
{
    public function __construct(
        private readonly User $user,
    ) {
    }

    public function name(): string
    {
        return _('Links read');
    }

    public function description(): string
    {
        return _('Find here all the links you’ve marked as read.');
    }

    public function url(): string
    {
        return \Minz\Url::absoluteFor('read list');
    }

    public function user(): User
    {
        return $this->user;
    }

    /**
     * @return Link[]
     */
    public function links(?utils\Pagination $pagination = null): array
    {
        return Link::listRead($this->user, $pagination);
    }

    public function countLinks(): int
    {
        return Link::countRead($this->user);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->user->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:{$this->user->id}/read";
    }
}
