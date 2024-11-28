<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds
{
    /**
     * Show the feed of a link.
     *
     * @request_param string id
     *
     * @response 404
     *     if the link doesn't exist or is inaccessible
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id', '');
        $link = models\Link::find($link_id);

        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $locale = $link->owner()->locale;
        utils\Locale::setCurrentLocale($locale);

        $response = Response::ok('links/feeds/show.atom.xml.php', [
            'link' => $link,
            'messages' => $link->messages(),
            'user_agent' => \App\Configuration::$application['user_agent'],
        ]);
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        return $response;
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /links/:id/feed.atom.xml
     */
    public function alias(Request $request): Response
    {
        $link_id = $request->param('id');
        $url = \Minz\Url::for('link feed', ['id' => $link_id]);
        return Response::movedPermanently($url);
    }
}
