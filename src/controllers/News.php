<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * Handle the requests related to the news.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class News extends BaseController
{
    /**
     * Show the news page.
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 200
     */
    public function index(): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('news'));

        $news = $user->news();
        $links = $news->links(['published_at', 'number_notes']);
        $links_timeline = new utils\LinksTimeline($links);

        return Response::ok('news/index.phtml', [
            'news' => $news,
            'links_timeline' => $links_timeline,
            'no_news' => \Minz\Flash::pop('no_news'),
        ]);
    }

    /**
     * Fill the news page with links to read (from bookmarks and followed
     * collections)
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 400
     *     if csrf is invalid
     * @response 302 /news
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('news'));

        $csrf = $request->parameters->getString('csrf', '');

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('news/index.phtml', [
                'news' => $user->news(),
                'links_timeline' => new utils\LinksTimeline([]),
                'no_news' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $journal = new models\Journal($user);
        $count = $journal->fill(max: 50);

        if ($count === 0) {
            \Minz\Flash::set('no_news', true);
        }

        return Response::redirect('news');
    }

    /**
     * Return a JSON telling if there are new links available for the news.
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 200
     */
    public function showAvailable(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('news'));

        return Response::json(200, [
            'available' => models\Link::anyFromFollowedCollections($user->id),
        ]);
    }
}
