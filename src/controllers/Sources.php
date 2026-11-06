<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sources extends BaseController
{
    /**
     * List the sources followed by the current user.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $sources = $user->followedSources();

        models\link_lists\Preloader::for($sources)
            ->publishers()
            ->countStreamsFor($user);

        $importation_form = new forms\importations\OpmlImportation(options: [
            'user' => $user,
        ]);

        return Response::ok('sources/index.html.twig', [
            'sources' => $sources,
            'importation_form' => $importation_form,
        ]);
    }

    /**
     * Show the page of a source.
     *
     * @request_param string id
     * @request_param integer page
     *
     * @response 404
     *     If the requested page is out of bound.
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the source requires the users to be logged in while they are not.
     * @throws \Minz\Errors\MissingRecordError
     *     If the source doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the source.
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested page is out of the pagination bounds.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $source = models\Source::requireFromRequest($request);

        if ($user) {
            auth\Access::require($user, 'view', $source);
        } elseif (!auth\Access::can($user, 'view', $source)) {
            auth\CurrentUser::require();
        }

        $can_update = auth\Access::can($user, 'update', $source);

        $access_is_shared = $user && $source->sharedWith($user);
        $number_links = models\Link::countByList($source, [
            'hidden' => $can_update || $access_is_shared,
        ]);
        $number_per_page = $can_update ? 29 : 30; // the button to add a link counts for 1!
        $pagination_page = $request->parameters->getInteger('page', 1);
        $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);

        $topics = $source->topics();
        $topics = utils\Sorter::localeSort($topics, 'label');

        $links = $source->links(
            ['published_at'],
            [
                'hidden' => $can_update || $access_is_shared,
                'offset' => $pagination->currentOffset(),
                'limit' => $pagination->numberPerPage(),
            ]
        );

        models\links\Preloader::for($links)
            ->originsFor($user)
            ->urlStatusesFor($user)
            ->numberCollectionsFor($user)
            ->numberNotes();

        return Response::ok('sources/show.html.twig', [
            'source' => $source,
            'topics' => $topics,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }
}
