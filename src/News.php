<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the news.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class News
{
    /**
     * Show the news page.
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function index()
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $links = $user->newsLinks();

        return Response::ok('news/index.phtml', [
            'links' => $links,
        ]);
    }

    /**
     * Fill the news page with links to read (from bookmarks)
     *
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 400
     *     if csrf is invalid
     * @response 302 /news
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function fill($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('news/index.phtml', [
                'links' => [],
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link_dao = new models\dao\Link();
        $db_links = $link_dao->listBookmarksRandomlyByUserId($user->id);
        $links = [];
        $total_reading_time = 0;
        foreach ($db_links as $db_link) {
            $reading_time = $db_link['reading_time'];
            if ($total_reading_time + $reading_time >= 75) {
                continue;
            }

            $link_dao->update($db_link['id'], [
                'in_news' => 1,
            ]);
            $total_reading_time = $total_reading_time + $reading_time;

            if ($total_reading_time >= 50) {
                break;
            }
        }

        return Response::redirect('news');
    }
}
