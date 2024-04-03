<?php

namespace App\controllers\profiles;

use Minz\Request;
use Minz\Response;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml
{
    /**
     * Show the collections of a user as an OPML file.
     *
     * @request_param string id
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
        $user = models\User::find($user_id);

        if (!$user || $user->isSupportUser()) {
            return Response::notFound('not_found.phtml');
        }

        utils\Locale::setCurrentLocale($user->locale);
        $collections = $user->collections([], [
            'private' => false,
        ]);
        $collections = utils\Sorter::localeSort($collections, 'name');

        return Response::ok('profiles/opml/show.opml.xml.php', [
            'user' => $user,
            'collections' => $collections,
            'user_agent' => \Minz\Configuration::$application['user_agent'],
        ]);
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /p/:id/opml.xml
     */
    public function alias(Request $request): Response
    {
        $user_id = $request->param('id');
        $url = \Minz\Url::for('profile opml', ['id' => $user_id]);
        $response = new Response(301);
        $response->setHeader('Location', $url);
        return $response;
    }
}
