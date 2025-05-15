<?php

namespace App\controllers\api\v1;

use App\models;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Searches extends BaseController
{
    /**
     * @request_param string url
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 400
     *     If the URL is invalid.
     * @response 200
     */
    public function index(Request $request): Response
    {
        // TODO refactor with the Links\Searches controller
        // TODO get user from a user_id param
        $user = $this->requireCurrentUser();
        $support_user = models\User::supportUser();

        $url = $request->param('url', '');
        $url = \SpiderBits\Url::sanitize($url);

        $url_hash = models\Link::hashUrl($url);

        $link = models\Link::findComputedBy([
            'user_id' => $user->id,
            'url_hash' => $url_hash,
        ], ['number_comments']);

        if (!$link) {
            $link = models\Link::findBy([
                'user_id' => $support_user->id,
                'url_hash' => $url_hash,
                'is_hidden' => 0,
            ]);
        }

        if (!$link) {
            $link = new models\Link($url, $support_user->id, false);

            if (!$link->validate()) {
                return $this->badRequestWithErrors($link->errors(format: false));
            }

            $link_fetcher_service = new services\LinkFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);
            $link_fetcher_service->fetch($link);
        }

        return Response::json(200, [
            'links' => [
                [
                    'title' => $link->title,
                    'url' => $link->url,
                    'reading_time' => $link->reading_time,
                    'tags' => $link->tags,
                ],
            ],
        ]);
    }
}
