<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Streams extends BaseController
{
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\streams\Stream();

        return Response::ok('streams/new.html.twig', [
            'form' => $form,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $stream = $user->initStream();
        $form = new forms\streams\Stream(model: $stream);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/new.html.twig', [
                'form' => $form,
            ]);
        }

        $stream = $form->model();

        $stream->save();

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
    }

    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $stream = models\Stream::requireFromRequest($request);

        $stream_view = models\StreamView::buildFromRequest($stream, $request);

        return Response::ok('streams/show.html.twig', [
            'stream' => $stream,
            'stream_view' => $stream_view,
        ]);
    }
}
