<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests related to the journal.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Journal extends BaseController
{
    /**
     * Show the journal page.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function index(): Response
    {
        $user = auth\CurrentUser::require();

        $journal = $user->journal();
        $links = $journal->links(['published_at']);

        models\links\Preloader::for($links)
            ->sources()
            ->originsFor($user)
            ->urlStatusesFor($user)
            ->numberCollectionsFor($user);

        $links_timeline = new utils\LinksTimeline($links);

        $form = new forms\FillJournal();

        return Response::ok('journal/index.html.twig', [
            'journal' => $journal,
            'links_timeline' => $links_timeline,
            'no_candidates' => \Minz\Flash::pop('no_candidates'),
            'form' => $form,
        ]);
    }

    /**
     * Fill the journal with links to read from followed collections.
     *
     * @request_param string csrf_token
     *
     * @response 400
     *     If the CSRF token is invalid.
     * @response 302 /journal
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\FillJournal();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('journal/index.html.twig', [
                'journal' => $user->journal(),
                'links_timeline' => new utils\LinksTimeline([]),
                'no_candidates' => false,
                'form' => $form,
            ]);
        }

        $journal = $user->journal();
        $count = $journal->fill(max: 50);

        if ($count === 0) {
            \Minz\Flash::set('no_candidates', true);
        }

        return Response::redirect('journal');
    }

    /**
     * Return a JSON telling if there are candidate links for the journal.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function hasCandidates(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        return Response::json(200, [
            'candidates' => $user->journal()->hasCandidates(),
        ]);
    }

    /**
     * Handle old /news URL and redirect to /journal
     *
     * @response 301 /journal
     */
    public function news(Request $request): Response
    {
        $url = \Minz\Url::for('journal');

        $query_string = $request->server->getString('QUERY_STRING');
        if ($query_string) {
            $url .= '?' . $query_string;
        }

        return Response::movedPermanently($url);
    }
}
