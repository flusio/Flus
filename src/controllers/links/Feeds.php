<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

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
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $link = models\Link::find($link_id);

        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $locale = $link->owner()->locale;
        utils\Locale::setCurrentLocale($locale);

        return Response::ok('links/feeds/show.atom.xml.php', [
            'link' => $link,
            'messages' => $link->messages(),
            'user_agent' => \Minz\Configuration::$application['user_agent'],
        ]);
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /links/:id/feed.atom.xml
     */
    public function alias($request)
    {
        $link_id = $request->param('id');
        $url = \Minz\Url::for('link feed', ['id' => $link_id]);
        $response = new Response(301);
        $response->setHeader('Location', $url);
        return $response;
    }
}
