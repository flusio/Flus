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
     * @json_param string url
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 400
     *     If the URL is invalid.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $jsonRequest = $this->toJsonRequest($request);
        $url = $jsonRequest->parameters->getString('url', '');

        $link = $user->linkByUrl($url);

        if (!$link->validate()) {
            return $this->badRequest($link->errors(format: false));
        }

        if ($link->to_be_fetched) {
            $link_fetcher_service = new services\LinkFetcher();
            $link_fetcher_service->fetch($link);
        }

        return Response::json(200, [
            'links' => [
                [
                    'id' => $link->id,
                    'title' => $link->title,
                    'url' => $link->url,
                    'reading_time' => $link->reading_time,
                    'tags' => $link->tags,
                    'is_read' => $link->isReadBy($user),
                    'is_read_later' => $link->isInBookmarksOf($user),
                    'collections' => array_column($link->collections(), 'id'),
                ],
            ],
        ]);
    }
}
