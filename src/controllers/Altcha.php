<?php

namespace App\controllers;

use App\services;
use Minz\Request;
use Minz\Response;

class Altcha
{
    public function show(Request $request): Response
    {
        try {
            $altcha_service = new services\AltchaService();
            $challenge = $altcha_service->buildChallenge();
        } catch (\Exception $e) {
            return Response::internalServerError('internal_server_error.phtml', [
                'error' => $e->getMessage(),
            ]);
        }

        return Response::json(200, $challenge->toArray());
    }
}
