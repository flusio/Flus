<?php

namespace flusio\controllers\profiles;

use Minz\Request;
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
     * Show the feed of a user.
     *
     * @request_param string id
     * @request_param boolean direct
     *     Indicate if <link rel=alternate> should point directly to the
     *     external websites (true) or not (false, default).
     *
     * @response 404
     *    If the requested profile doesn’t exist or is associated to the
     *    support user.
     * @response 200
     *    On success
     */
    public function show(Request $request): Response
    {
        $user_id = $request->param('id', '');
        $direct = $request->paramBoolean('direct', false);

        $user = models\User::find($user_id);
        if (!$user || $user->isSupportUser()) {
            return Response::notFound('not_found.phtml');
        }

        utils\Locale::setCurrentLocale($user->locale);
        $links = $user->links(['published_at'], [
            'unshared' => false,
            'limit' => 30,
        ]);

        $response = Response::ok('profiles/feeds/show.atom.xml.php', [
            'user' => $user,
            'links' => $links,
            'user_agent' => \Minz\Configuration::$application['user_agent'],
            'direct' => $direct,
        ]);
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        return $response;
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /p/:id/feed.atom.xml
     */
    public function alias(Request $request): Response
    {
        $user_id = $request->param('id');
        $url = \Minz\Url::for('profile feed', ['id' => $user_id]);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $url .= '?' . $_SERVER['QUERY_STRING'];
        }
        return Response::movedPermanently($url);
    }
}
