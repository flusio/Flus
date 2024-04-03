<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\jobs;
use App\models;
use App\utils;

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
     * @request_param boolean mark_as_read
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 200
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id', '');
        $mark_as_read = $request->paramBoolean('mark_as_read', false);
        $from = $request->param('from', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        $messages = [];
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if ($link->user_id === $user->id) {
            $messages = $link->messages();
        } else {
            $existing_link = models\Link::findBy([
                'user_id' => $user->id,
                'url_lookup' => utils\Belt::removeScheme($link->url),
            ]);
            if ($existing_link) {
                $link = $existing_link;
                $messages = $link->messages();
            }
        }

        if (auth\LinksAccess::canUpdate($user, $link)) {
            $collection_ids = array_column($link->collections(), 'id');
        } else {
            $collection_ids = [];
        }

        $groups = models\Group::listBy(['user_id' => $user->id]);
        $groups = utils\Sorter::localeSort($groups, 'name');

        $collections = $user->collections();
        $collections = utils\Sorter::localeSort($collections, 'name');
        $groups_to_collections = utils\Grouper::groupBy($collections, 'group_id');

        $shared_collections = $user->sharedCollections([], [
            'access_type' => 'write',
        ]);
        $shared_collections = utils\Sorter::localeSort($shared_collections, 'name');
        $collections_by_others = models\Collection::listWritableContainingNotOwnedLinkWithUrl(
            $user->id,
            $link->url_lookup,
        );
        $collections_by_others = utils\Sorter::localeSort($collections_by_others, 'name');

        $mastodon_configured = models\MastodonAccount::existsBy([
            'user_id' => $user->id,
        ]);

        return Response::ok('links/collections/index.phtml', [
            'link' => $link,
            'collection_ids' => $collection_ids,
            'new_collection_names' => [],
            'name_max_length' => models\Collection::NAME_MAX_LENGTH,
            'groups' => $groups,
            'groups_to_collections' => $groups_to_collections,
            'shared_collections' => $shared_collections,
            'collections_by_others' => $collections_by_others,
            'mark_as_read' => $mark_as_read,
            'messages' => $messages,
            'comment' => '',
            'share_on_mastodon' => false,
            'mastodon_configured' => $mastodon_configured,
            'from' => $from,
        ]);
    }

    /**
     * Update the link collections list
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param string[] new_collection_names
     * @request_param boolean is_hidden
     * @request_param boolean mark_as_read
     * @request_param string comment
     * @request_param string share_on_mastodon
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected.
     * @response 404
     *     If the link is not found.
     * @response 302 :from
     *     If CSRF, collection_ids or new_collection_names are invalid.
     * @response 302 :from
     *     On success.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id', '');
        /** @var string[] */
        $new_collection_ids = $request->paramArray('collection_ids', []);
        /** @var string[] */
        $new_collection_names = $request->paramArray('new_collection_names', []);
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $mark_as_read = $request->paramBoolean('mark_as_read', false);
        $comment = trim($request->param('comment', ''));
        $share_on_mastodon = $request->paramBoolean('share_on_mastodon');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!$user->canWriteCollections($new_collection_ids)) {
            \Minz\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        foreach ($new_collection_names as $name) {
            $new_collection = models\Collection::init($user->id, $name, '', false);

            $errors = $new_collection->validate();
            if ($errors) {
                \Minz\Flash::set('errors', $errors);
                return Response::found($from);
            }

            $new_collection->save();
            $new_collection_ids[] = $new_collection->id;
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            $link = $user->obtainLink($link);
            utils\SourceHelper::setLinkSource($link, $from);
        }

        $link->is_hidden = $is_hidden;
        $link->save();

        models\LinkToCollection::setCollections($link->id, $new_collection_ids);

        if ($comment) {
            $message = new models\Message($user->id, $link->id, $comment);
            $message->save();
        }

        if ($mark_as_read) {
            models\LinkToCollection::markAsRead($user, [$link->id]);
        }

        $mastodon_configured = models\MastodonAccount::existsBy([
            'user_id' => $user->id,
        ]);

        if ($mastodon_configured && $share_on_mastodon) {
            $message_id = isset($message) ? $message->id : null;
            $share_on_mastodon_job = new jobs\ShareOnMastodon();
            $share_on_mastodon_job->performAsap($user->id, $link->id, $message_id);
        }

        return Response::found($from);
    }
}
