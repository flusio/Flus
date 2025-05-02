<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read extends BaseController
{
    /**
     * Mark links of the collection as read and remove them from bookmarks.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string from
     * @request_param date date
     * @request_param string source
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function create(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');
        $collection_id = $request->parameters->getString('id', '');
        $date = $request->parameters->getDatetime('date', format: 'Y-m-d');
        $source = $request->parameters->getString('source', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        $links = [];

        $options = [];

        if ($date) {
            $options['published_date'] = $date;
        }

        if ($source) {
            $options['source'] = $source;
        }

        if ($collection && auth\CollectionsAccess::canUpdateRead($user, $collection)) {
            $links = $collection->links(options: $options);
        } elseif ($collection && $user->isFollowing($collection->id)) {
            $options['hidden'] = $collection->sharedWith($user);
            $collection_links = $collection->links(options: $options);
            $links = $user->obtainLinks($collection_links);
            list($source_type, $source_resource_id) = utils\SourceHelper::extractFromPath($from);

            $links_to_create = [];
            foreach ($links as $link) {
                if (!$link->isPersisted()) {
                    $link->created_at = \Minz\Time::now();
                    if ($source_type) {
                        $link->source_type = $source_type;
                        $link->source_resource_id = $source_resource_id;
                    }
                    $links_to_create[] = $link;
                }
            }
            models\Link::bulkInsert($links_to_create);
        } else {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $user->markAsRead($links);

        return Response::found($from);
    }

    /**
     * Remove links of the collection from news and add them to bookmarks.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string from
     * @request_param date date
     * @request_param string source
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function later(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');
        $collection_id = $request->parameters->getString('id', '');
        $date = $request->parameters->getDatetime('date', format: 'Y-m-d');
        $source = $request->parameters->getString('source', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        $links = [];

        $options = [];

        if ($date) {
            $options['published_date'] = $date;
        }

        if ($source) {
            $options['source'] = $source;
        }

        if ($collection && auth\CollectionsAccess::canUpdateRead($user, $collection)) {
            $links = $collection->links(options: $options);
        } elseif ($collection && $user->isFollowing($collection->id)) {
            $options['hidden'] = $collection->sharedWith($user);
            $collection_links = $collection->links(options: $options);
            $links = $user->obtainLinks($collection_links);

            list($source_type, $source_resource_id) = utils\SourceHelper::extractFromPath($from);

            $links_to_create = [];
            foreach ($links as $link) {
                if (!$link->isPersisted()) {
                    $link->created_at = \Minz\Time::now();
                    if ($source_type) {
                        $link->source_type = $source_type;
                        $link->source_resource_id = $source_resource_id;
                    }
                    $links_to_create[] = $link;
                }
            }
            models\Link::bulkInsert($links_to_create);
        } else {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $user->markAsReadLater($links);

        return Response::found($from);
    }

    /**
     * Remove links of the collection from news and bookmarks and add them to the never list.
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string from
     * @request_param date date
     * @request_param string source
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function never(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');
        $collection_id = $request->parameters->getString('id', '');
        $date = $request->parameters->getDatetime('date', format: 'Y-m-d');
        $source = $request->parameters->getString('source', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        $links = [];

        $options = [];

        if ($date) {
            $options['published_date'] = $date;
        }

        if ($source) {
            $options['source'] = $source;
        }

        if ($collection && auth\CollectionsAccess::canUpdateRead($user, $collection)) {
            $links = $collection->links(options: $options);
        } elseif ($collection && $user->isFollowing($collection->id)) {
            $options['hidden'] = $collection->sharedWith($user);
            $collection_links = $collection->links(options: $options);
            $links = $user->obtainLinks($collection_links);

            $links_to_create = [];
            foreach ($links as $link) {
                if (!$link->isPersisted()) {
                    $link->created_at = \Minz\Time::now();
                    $links_to_create[] = $link;
                }
            }
            models\Link::bulkInsert($links_to_create);
        } else {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $user->removeFromJournal($links);

        return Response::found($from);
    }
}
