<?php

namespace App\controllers\profiles;

use App\auth;
use App\controllers\BaseController;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

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
     * @response 200
     *    On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user doesn't exist.
     */
    public function show(Request $request): Response
    {
        $direct = $request->parameters->getBoolean('direct');

        $user = models\User::requireFromRequest($request);

        utils\Locale::setCurrentLocale($user->locale);
        $links = $user->links(['published_at'], [
            'unshared' => false,
            'limit' => 30,
        ]);

        // Deduplicate the links by id: a profile can list the same link several
        // times (e.g. two collections publishing it), while the Atom entries
        // must have unique ids.
        $links_by_id = [];
        foreach ($links as $link) {
            $links_by_id[$link->id] ??= $link;
        }
        $links = array_values($links_by_id);

        return Response::ok('profiles/feeds/show.atom.xml.twig', [
            'user' => $user,
            'links' => $links,
            'direct' => $direct,
        ]);
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
        $user_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('profile feed', ['id' => $user_id]);

        $query_string = $request->server->getString('QUERY_STRING');
        if ($query_string) {
            $url .= '?' . $query_string;
        }

        return Response::movedPermanently($url);
    }
}
