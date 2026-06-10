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
class Links extends BaseController
{
    /**
     * @request_param string id
     * @request_param string tag
     *
     * @response 200
     *    On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user doesn't exist.
     */
    public function index(Request $request): Response
    {
        $user = models\User::requireFromRequest($request);

        $tag = $request->parameters->getString('tag', '');
        if (!utils\Tag::isValid("#{$tag}")) {
            $tag = '';
        }

        $current_user = auth\CurrentUser::get();

        $number_links = $user->countLinks([
            'unshared' => false,
            'tag' => $tag,
        ]);
        $pagination_page = $request->parameters->getInteger('page', 1);
        $pagination = new utils\Pagination($number_links, 30, $pagination_page);

        $links = $user->links(['published_at', 'number_notes'], [
            'unshared' => false,
            'tag' => $tag,
            'offset' => $pagination->currentOffset(),
            'limit' => $pagination->numberPerPage(),
        ]);

        return Response::ok('profiles/links/index.html.twig', [
            'user' => $user,
            'tag' => $tag,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }
}
