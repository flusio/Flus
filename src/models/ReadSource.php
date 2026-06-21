<?php

namespace App\models;

use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ReadSource extends Source
{
    public function __construct(
        private readonly User $owner,
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

    public function owner(): User
    {
        return $this->owner;
    }

    /**
     * @return Link[]
     */
    public function links(?utils\Pagination $pagination = null): array
    {
        return Link::listRead($this->owner, $pagination);
    }

    public function countLinks(): int
    {
        return Link::countRead($this->owner);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->owner->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:{$this->owner->id}/read";
    }
}
