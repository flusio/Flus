<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\jobs;
use App\models;
use App\services;
use App\utils;

/**
 * Handle the requests related to the links collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections extends BaseController
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
        $link_id = $request->parameters->getString('id', '');
        $mark_as_read = $request->parameters->getBoolean('mark_as_read');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        $notes = [];
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if ($link->user_id !== $user->id) {
            $existing_link = models\Link::findBy([
                'user_id' => $user->id,
                'url_hash' => models\Link::hashUrl($link->url),
            ]);

            if ($existing_link) {
                $link = $existing_link;
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
            $link->url_hash,
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
            'content' => '',
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
     * @request_param string content
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
        $link_id = $request->parameters->getString('id', '');
        /** @var string[] */
        $new_collection_ids = $request->parameters->getArray('collection_ids', []);
        /** @var string[] */
        $new_collection_names = $request->parameters->getArray('new_collection_names', []);
        $is_hidden = $request->parameters->getBoolean('is_hidden');
        $mark_as_read = $request->parameters->getBoolean('mark_as_read');
        $content = trim($request->parameters->getString('content', ''));
        $share_on_mastodon = $request->parameters->getBoolean('share_on_mastodon');
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!$user->canWriteCollections($new_collection_ids)) {
            \Minz\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        foreach ($new_collection_names as $name) {
            $new_collection = models\Collection::init($user->id, $name, '', false);

            if (!$new_collection->validate()) {
                \Minz\Flash::set('errors', $new_collection->errors());
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

        if ($content) {
            $note = new models\Note($user->id, $link->id, $content);
            $note->save();
        }

        if ($mark_as_read) {
            models\LinkToCollection::markAsRead($user, [$link->id]);
        }

        services\LinkTags::refresh($link);

        $mastodon_configured = models\MastodonAccount::existsBy([
            'user_id' => $user->id,
        ]);

        if ($mastodon_configured && $share_on_mastodon) {
            $note_id = isset($note) ? $note->id : null;
            $share_on_mastodon_job = new jobs\ShareOnMastodon();
            $share_on_mastodon_job->performAsap($user->id, $link->id, $note_id);
        }

        return Response::found($from);
    }
}
