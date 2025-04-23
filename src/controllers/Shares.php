<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Shares extends BaseController
{
    /**
     * Allow to share a URL to Flus.
     *
     * @request_param string title (ignored)
     * @request_param string text
     * @request_param string url
     *
     * @response 302 /links/search?autosubmit=1&url=<url|text>
     */
    public function new(Request $request): Response
    {
        $url = $request->param('url', '');
        $text = $request->param('text', '');

        if ($url === '') {
            $url = $text;
        }

        return Response::redirect('show search link', [
            'url' => $url,
            'autosubmit' => true,
        ]);
    }
}
