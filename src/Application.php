<?php

namespace flusio;

/**
 * This is the central class of the application. It declares routes and
 * executes a Request to return a Response.
 *
 * It is called from the public/index.php file which is the entrypoint from a
 * Web browser.
 *
 * Example:
 *
 * $request = new \Minz\Request('get', '/');
 * $application = new \flusio\Application();
 * $response = $application->run($request);
 * echo $response->render();
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Application
{
    /** @var \Minz\Engine **/
    private $engine;

    /**
     * Setup a Router and declare its routes.
     */
    public function __construct()
    {
        // This provides utility functions to be used in the Views
        include_once('utils/view_helpers.php');

        // Load the router with its routes
        $router = new \Minz\Router();
        Routes::load($router);
        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);
    }

    /**
     * Declare global View variables and execute a request.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function run($request)
    {
        if (!utils\CurrentUser::sessionToken()) {
            utils\CurrentUser::setSessionToken($request->cookie('flusio_session_token'));
        }
        $current_user = utils\CurrentUser::get();

        // Redirect the user if she didn't validated its account after the
        // first day.
        if ($current_user && $this->mustRedirectToValidation($request, $current_user)) {
            return \Minz\Response::redirect('account validation');
        }

        // Redirect the user if its subscription is overdue
        if ($current_user && $this->mustRedirectToAccount($request, $current_user)) {
            return \Minz\Response::redirect('account');
        }

        // Setup current localization
        if ($current_user) {
            $locale = $current_user->locale;
        } elseif (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } else {
            $http_accept_language = $request->header('HTTP_ACCEPT_LANGUAGE');
            $locale = utils\Locale::best($http_accept_language);
        }
        utils\Locale::setCurrentLocale($locale);

        // Force CSRF token to avoid weird issues when user did nothing for a while
        if ($current_user) {
            $csrf = new \Minz\CSRF();
            $csrf->setToken($current_user->csrf);
        }

        $errors = utils\Flash::pop('errors', []);
        $error = utils\Flash::pop('error');
        $status = utils\Flash::pop('status');

        $response = $this->engine->run($request, [
            'not_found_view_pointer' => 'not_found.phtml',
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
            'controller_namespace' => '\\flusio\\controllers',
        ]);

        $app_conf = \Minz\Configuration::$application;
        \Minz\Output\View::declareDefaultVariables([
            'environment' => \Minz\Configuration::$environment,
            'brand' => $app_conf['brand'],
            'errors' => $errors,
            'error' => $error,
            'status' => $status,
            'current_action_pointer' => $request->param('_action_pointer'),
            'canonical' => null,
            'available_locales' => utils\Locale::availableLocales(),
            'current_locale' => $locale,
            'current_user' => $current_user,
            'current_tab' => null,
            'current_page' => null,
            'styles' => [],
            'feed' => [],
            'open_graph' => [],
            'javascript_configuration' => json_encode(include('utils/javascript_configuration.php')),
            'no_layout' => $request->header('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest',
            'subscriptions_enabled' => $app_conf['subscriptions_enabled'],
            'demo' => $app_conf['demo'],
            'registrations_opened' => $app_conf['registrations_opened'],
        ]);

        return $response;
    }

    /**
     * Return true if the user must validate its account (i.e. when not
     * validated after its first day).
     *
     * @param \Minz\Request $request
     * @param \flusio\models\User $user
     *
     * @return boolean
     */
    public function mustRedirectToValidation($request, $user)
    {
        if (!$user->mustValidateEmail()) {
            return false;
        }

        $path = $request->path();
        $path_is_authorized = (
            utils\Belt::startsWith($path, '/my/') ||
            utils\Belt::startsWith($path, '/logout') ||
            utils\Belt::startsWith($path, '/terms') ||
            utils\Belt::startsWith($path, '/onboarding') ||
            utils\Belt::startsWith($path, '/src/assets')
        );
        return !$path_is_authorized;
    }

    /**
     * Return true if the user must renew its subscription
     *
     * @param \Minz\Request $request
     * @param \flusio\models\User $user
     *
     * @return boolean
     */
    public function mustRedirectToAccount($request, $user)
    {
        $app_conf = \Minz\Configuration::$application;
        if (!$app_conf['subscriptions_enabled']) {
            return false;
        }

        if (!$user->isSubscriptionOverdue()) {
            return false;
        }

        $path = $request->path();
        $path_is_authorized = (
            utils\Belt::startsWith($path, '/my/') ||
            utils\Belt::startsWith($path, '/logout') ||
            utils\Belt::startsWith($path, '/terms') ||
            utils\Belt::startsWith($path, '/onboarding') ||
            utils\Belt::startsWith($path, '/src/assets')
        );
        return !$path_is_authorized;
    }
}
