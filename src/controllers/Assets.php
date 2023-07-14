<?php

namespace flusio\controllers;

use Minz\Request;
use Minz\Response;
use flusio\utils;

/**
 * Manipulate the assets of the app (under src/assets/)
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Assets
{
    /**
     * Return the asset file. This is useful for source mapping!
     *
     * @see https://www.html5rocks.com/en/tutorials/developertools/sourcemaps/
     *
     * @request_param string * The asset path relative to the src/assets/ folder.
     *
     * @response 404 If the file doesn't exist or cannot be served (i.e. not
     *               under the src/assets/ folder)
     * @response 200 Return the asset file if it can be served
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show(Request $request): Response
    {
        $requested_path = $request->param('*');
        $assets_path = \Minz\Configuration::$app_path . '/src/assets';
        $asset_path = realpath($assets_path . '/' . $requested_path);

        if (!$asset_path) {
            return Response::text(404, 'This file doesn’t exist.');
        }

        if (!str_starts_with($asset_path, $assets_path)) {
            \Minz\Log::warning(
                'Someone tries to access a file that is not under the src/assets/ path!'
            );
            return Response::text(404, 'You’ll not get this file!');
        }

        $output = new \Minz\Output\File($asset_path);
        return new Response(200, $output);
    }
}
