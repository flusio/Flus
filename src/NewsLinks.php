<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the news.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLinks
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

        $news_links = $user->newsLinks();

        return Response::ok('news_links/index.phtml', [
            'news_links' => $news_links,
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
            return Response::badRequest('news_links/index.phtml', [
                'news_links' => [],
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link_dao = new models\dao\Link();
        $news_link_dao = new models\dao\NewsLink();
        $db_links = $link_dao->listBookmarksRandomlyByUserId($user->id);
        $links = [];
        $total_reading_time = 0;
        foreach ($db_links as $db_link) {
            $link = new models\Link($db_link);
            if ($total_reading_time + $link->reading_time >= 75) {
                continue;
            }

            $news_link = models\NewsLink::initFromLink($link, $user->id);
            $values = $news_link->toValues();
            $values['created_at'] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
            // The id should be set by the DB. Here, PostgreSQL fails because
            // its value is null.
            unset($values['id']);
            $news_link_dao->create($values);

            $total_reading_time = $total_reading_time + $link->reading_time;
            if ($total_reading_time >= 50) {
                break;
            }
        }

        return Response::redirect('news');
    }

    /**
     * Remove a link from news and bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function read($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $news_link = $user->newsLink($news_link_id);
        if (!$news_link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $news_link_dao = new models\dao\NewsLink();

        // If the link is in the bookmarks, let's unbookmark it.
        $link = $user->linkByUrl($news_link->url);
        if ($link) {
            $bookmarks = $user->bookmarks();
            $links_to_collections_dao->detach($link->id, [$bookmarks->id]);
        }

        // Then, hide the news so it will no longer be suggested to the user.
        $news_link->is_hidden = true;
        $news_link_dao->save($news_link);

        return Response::found($from);
    }

    /**
     * Remove a link from news only.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function readLater($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $news_link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $news_link = $user->newsLink($news_link_id);
        if (!$news_link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_dao = new models\dao\Link();
        $news_link_dao = new models\dao\NewsLink();

        // First, we want the link with corresponding URL to exist for the
        // current user (or it would be impossible to bookmark it correctly).
        // If it doesn't exist, let's create it in DB from the $news_link variable.
        $link = $user->linkByUrl($news_link->url);
        if (!$link) {
            $link = models\Link::initFromNews($news_link, $user->id);
            $link_dao->save($link);
        }

        // Then, we check if the link is bookmarked. If it isn't, bookmark it.
        $bookmarks = $user->bookmarks();
        $actual_collection_ids = array_column($link->collections(), 'id');
        if (!in_array($bookmarks->id, $actual_collection_ids)) {
            $links_to_collections_dao->attach($link->id, [$bookmarks->id]);
        }

        // Then, remove the news (we don't hide it since it would no longer be
        // suggested to the user).
        $news_link_dao->delete($news_link->id);

        return Response::found($from);
    }
}
