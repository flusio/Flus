<?php

namespace flusio\auth;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLinksAccess
{
    public static function canUpdate($user, $news_link)
    {
        return $user && $news_link && $user->id === $news_link->user_id;
    }
}
