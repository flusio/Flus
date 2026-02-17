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
class Mastodon extends BaseController
{
    /**
     * Show the page to configure the Mastodon host/sharing.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $mastodon_account = models\MastodonAccount::findByUser($user);

        if ($mastodon_account && $mastodon_account->isSetup()) {
            $form = new forms\mastodon\EditMastodonAccount(model: $mastodon_account);
        } else {
            $form = new forms\mastodon\RequestMastodonAccount(options: [
                'user' => $user,
            ]);
        }

        return Response::ok('mastodon/show.html.twig', [
            'mastodon_account' => $mastodon_account,
            'form' => $form,
        ]);
    }

    /**
     * Request the access to a Mastodon app.
     *
     * @request_param string host
     * @request_param string csrf_token
     *
     * @response 302 /mastodon
     * @flash error
     *     If at least one of the parameters is invalid or if an error occurs.
     * @response 302 :host
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function requestAccess(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\mastodon\RequestMastodonAccount(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            $error = implode(' ', $form->errors());
            utils\Notification::error($error);

            return Response::redirect('mastodon');
        }

        try {
            $mastodon_service = $form->mastodonService();

            $mastodon_account = $form->mastodonAccount();
            $mastodon_account->save();
        } catch (services\MastodonError $error) {
            \Minz\Log::error($error->getMessage());

            utils\Notification::error(_('The Mastodon host returned an error, please try later.'));
            return Response::redirect('mastodon');
        }

        return Response::found($mastodon_service->authorizationUrl());
    }

    /**
     * Handle the authorization redirection from a Mastodon host.
     *
     * @request_param string code
     *
     * @response 302 /mastodon
     *     If the user has no associated MastodonAccount, or if the existing
     *     MastodonAccount is already authorized.
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function authorization(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $mastodon_account = models\MastodonAccount::findByUser($user);

        if (!$mastodon_account || $mastodon_account->isSetup()) {
            return Response::redirect('mastodon');
        }

        $form = new forms\mastodon\AuthorizeMastodonAccount([
            'code' => $request->parameters->getString('code', ''),
        ]);

        return Response::ok('mastodon/authorization.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Get an access token for the user Mastodon account.
     *
     * @request_param string code
     * @request_param string csrf_token
     *
     * @response 302 /mastodon
     *     If the user has no associated MastodonAccount, or if the existing
     *     MastodonAccount is already authorized.
     * @response 400
     *    If the CSRF token is invalid or if an error occurs.
     * @response 302 /mastodon
     *     On success.
     */
    public function authorize(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $mastodon_account = models\MastodonAccount::findByUser($user);

        if (!$mastodon_account || $mastodon_account->isSetup()) {
            return Response::redirect('mastodon');
        }

        $form = new forms\mastodon\AuthorizeMastodonAccount(options: [
            'mastodon_account' => $mastodon_account,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('mastodon/authorization.html.twig', [
                'form' => $form,
            ]);
        }

        try {
            $mastodon_account->access_token = $form->accessToken();
            $mastodon_account->username = $form->username();

            $mastodon_account->save();
        } catch (services\MastodonError $error) {
            \Minz\Log::error($error->getMessage());

            $form->addError(
                '@base',
                'server_error',
                _('The Mastodon host returned an error, please try later.'),
            );

            return Response::badRequest('mastodon/authorization.html.twig', [
                'form' => $form,
            ]);
        }

        return Response::redirect('mastodon');
    }

    /**
     * Update the options of the MastodonAccount of the current user.
     *
     * @request_param string link_to_comment
     * @request_param string post_scriptum
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /mastodon
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the user did not setup a Mastodon account.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $mastodon_account = models\MastodonAccount::findByUser($user);

        if (!$mastodon_account || !$mastodon_account->isSetup()) {
            throw new \Minz\Errors\MissingRecordError('User did not setup a Mastodon account.');
        }

        $form = new forms\mastodon\EditMastodonAccount(model: $mastodon_account);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('mastodon/show.html.twig', [
                'mastodon_account' => $mastodon_account,
                'form' => $form,
            ]);
        }

        $mastodon_account = $form->model();
        $mastodon_account->save();

        utils\Notification::success(_('Your changes have been successfully saved.'));

        return Response::redirect('mastodon');
    }

    /**
     * Delete the MastodonAccount of the current user.
     *
     * @request_param string csrf_token
     *
     * @response 302 /mastodon
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 /mastodon
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the user does not have a Mastodon account.
     */
    public function disconnect(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $mastodon_account = models\MastodonAccount::findByUser($user);

        if (!$mastodon_account) {
            throw new \Minz\Errors\MissingRecordError('User did not setup a Mastodon account.');
        }

        $form = new forms\mastodon\DeleteMastodonAccount();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::redirect('mastodon');
        }

        $mastodon_account->remove();

        return Response::redirect('mastodon');
    }
}
