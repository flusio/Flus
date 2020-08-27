<?php

namespace flusio\models\dao;

/**
 * Represent a link (displayed in news) in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLink extends \Minz\DatabaseModel
{
    use SaveHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\NewsLink::PROPERTIES);
        parent::__construct('news_links', 'id', $properties);
    }
}
