<?php

namespace App\controllers\links;

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
     * Show the feed of a link.
     *
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the link.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'view', $link);

        $locale = $link->owner()->locale;
        utils\Locale::setCurrentLocale($locale);

        $response = Response::ok('links/feeds/show.atom.xml.twig', [
            'link' => $link,
            'notes' => $link->notes(),
            'user_agent' => utils\UserAgent::get(),
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
        $link_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('link feed', ['id' => $link_id]);
        return Response::movedPermanently($url);
    }
}
