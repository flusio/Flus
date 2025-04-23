<?php

namespace App\controllers\profiles;

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
class Feeds extends BaseController
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
            'user_agent' => utils\UserAgent::get(),
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

        $query_string = $_SERVER['QUERY_STRING'] ?? null;
        if (is_string($query_string) && $query_string) {
            $url .= '?' . $query_string;
        }

        return Response::movedPermanently($url);
    }
}
