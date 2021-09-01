<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the links collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections
{
    /**
     * Show the page to update the link collections
     *
     * @request_param string id
     * @request_param string mode Either 'normal' (default) or 'news'
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $mode = $request->param('mode', 'normal');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if ($mode === 'news') {
            $collections = $user->collections(true);
            models\Collection::sort($collections, $user->locale);

            return Response::ok('links/collections/index_news.phtml', [
                'link' => $link,
                'collection_ids' => array_column($link->collections(), 'id'),
                'collections' => $collections,
                'comment' => '',
                'from' => $from,
            ]);
        } else {
            $collections = $user->collections();
            models\Collection::sort($collections, $user->locale);

            return Response::ok('links/collections/index.phtml', [
                'link' => $link,
                'collection_ids' => array_column($link->collections(), 'id'),
                'collections' => $collections,
                'from' => $from,
            ]);
        }
    }

    /**
     * Update the link collections list
     *
     * News mode allows to set is_hidden and add a comment. It also removes the
     * link from the news and from bookmarks and adds it to the read list.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param boolean is_hidden
     * @request_param string comment
     * @request_param string mode Either 'normal' (default) or 'news'
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 302 :from if CSRF or collection_ids are invalid
     * @response 302 :from
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $new_collection_ids = $request->paramArray('collection_ids', []);
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $comment = trim($request->param('comment', ''));
        $mode = $request->param('mode', 'normal');
        $from = $request->param('from');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!models\Collection::daoCall('existForUser', $user->id, $new_collection_ids)) {
            utils\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\LinkToCollection::setCollections($link->id, $new_collection_ids);

        if ($mode === 'news') {
            $link->is_hidden = $is_hidden;
            $link->save();

            if ($comment) {
                $message = models\Message::init($user->id, $link->id, $comment);
                $message->save();
            }

            models\LinkToCollection::markAsRead($user, [$link->id]);
        }

        return Response::found($from);
    }
}
