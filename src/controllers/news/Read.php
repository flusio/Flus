<?php

namespace flusio\controllers\news;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read
{
    /**
     * Mark news links as read and remove them from bookmarks.
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $news = $user->news();
        $link_ids = array_column($news->links(), 'id');
        models\LinkToCollection::markAsRead($user, $link_ids);

        return Response::found($from);
    }

    /**
     * Remove news links from news and add them to bookmarks.
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     * @flash error
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     */
    public function later($request)
    {
        $user = auth\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $news = $user->news();
        $link_ids = array_column($news->links(), 'id');
        models\LinkToCollection::markToReadLater($user, $link_ids);

        return Response::found($from);
    }
}
