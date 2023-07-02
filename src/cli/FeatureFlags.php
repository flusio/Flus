<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeatureFlags
{
    /**
     * List all the available feature flags types
     *
     * @response 200
     */
    public function index($request)
    {
        $types = models\FeatureFlag::VALID_TYPES;

        if ($types) {
            return Response::text(200, implode("\n", $types));
        } else {
            return Response::text(200, 'No types are available');
        }
    }

    /**
     * List all the users for which feature flags are enabled
     *
     * @response 200
     */
    public function flags($request)
    {
        $feature_flags = models\FeatureFlag::listAll();
        $output = [];
        foreach ($feature_flags as $feature_flag) {
            $user = $feature_flag->user();
            $output[] = "{$feature_flag->type} {$user->id} {$user->email}";
        }
        sort($output);

        if (!$output) {
            $output[] = 'No feature flags';
        }

        return Response::text(200, implode("\n", $output));
    }

    /**
     * Enable a feature flag for a given user
     *
     * @request_param string type
     * @request_param string user_id
     *
     * @response 400 if type is invalid
     * @response 404 if user doesn’t exist
     * @response 200
     */
    public function enable($request)
    {
        $type = $request->param('type');
        $user_id = $request->param('user_id');

        if (!in_array($type, models\FeatureFlag::VALID_TYPES)) {
            return Response::text(400, "{$type} is not a valid feature flag type");
        }

        $user = models\User::find($user_id);
        if (!$user) {
            return Response::text(404, "User {$user_id} doesn’t exist");
        }

        models\FeatureFlag::enable($type, $user->id);

        return Response::text(200, "{$type} is enabled for user {$user->id} ({$user->email})");
    }

    /**
     * Disable a feature flag for a given user
     *
     * @request_param string type
     * @request_param string user_id
     *
     * @response 404 if user doesn’t exist
     * @response 200
     */
    public function disable($request)
    {
        $type = $request->param('type');
        $user_id = $request->param('user_id');

        $user = models\User::find($user_id);
        if (!$user) {
            return Response::text(404, "User {$user_id} doesn’t exist");
        }

        models\FeatureFlag::disable($type, $user->id);

        return Response::text(200, "{$type} is disabled for user {$user->id} ({$user->email})");
    }
}
