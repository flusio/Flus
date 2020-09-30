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

        // Initialize the routes
        $router = new \Minz\Router();
        $router->addRoute('get', '/', 'Pages#home', 'home');

        // Registration
        $router->addRoute('get', '/registration', 'Registrations#new', 'registration');
        $router->addRoute('post', '/registration', 'Registrations#create', 'create user');
        $router->addRoute('get', '/registration/validation', 'Registrations#validation', 'registration validation');
        $router->addRoute(
            'post',
            '/registration/validation/email',
            'Registrations#resendValidationEmail',
            'resend validation email'
        );

        // Sessions
        $router->addRoute('get', '/login', 'Sessions#new', 'login');
        $router->addRoute('post', '/login', 'Sessions#create', 'create session');
        $router->addRoute('post', '/logout', 'Sessions#delete', 'logout');
        $router->addRoute('post', '/sessions/locale', 'Sessions#changeLocale', 'change locale');

        // Account
        $router->addRoute('get', '/account', 'Accounts#show', 'account');
        $router->addRoute('post', '/account', 'Accounts#update', 'update account');
        $router->addRoute('get', '/account/delete', 'Accounts#showDelete', 'show delete account');
        $router->addRoute('post', '/account/delete', 'Accounts#delete', 'delete account');

        // News page
        $router->addRoute('get', '/news', 'NewsLinks#index', 'news');
        $router->addRoute('post', '/news', 'NewsLinks#fill', 'fill news');
        $router->addRoute('get', '/news/preferences', 'NewsLinks#preferences', 'news preferences');
        $router->addRoute('post', '/news/preferences', 'NewsLinks#updatePreferences', 'update news preferences');
        $router->addRoute('get', '/news/:id/add', 'NewsLinks#adding', 'adding news');
        $router->addRoute('post', '/news/:id/add', 'NewsLinks#add', 'add news');
        $router->addRoute('post', '/news/:id/read-later', 'NewsLinks#readLater', 'read news later');
        $router->addRoute('post', '/news/:id/hide', 'NewsLinks#hide', 'hide news');

        // Collections
        $router->addRoute('get', '/collections', 'Collections#index', 'collections');
        $router->addRoute('get', '/collections/new', 'Collections#new', 'new collection');
        $router->addRoute('post', '/collections/new', 'Collections#create', 'create collection');
        $router->addRoute('get', '/collections/discover', 'Collections#discover', 'discover collections');
        $router->addRoute('get', '/collections/:id', 'Collections#show', 'collection');
        $router->addRoute('get', '/collections/:id/edit', 'Collections#edit', 'edit collection');
        $router->addRoute('post', '/collections/:id/edit', 'Collections#update', 'update collection');
        $router->addRoute('post', '/collections/:id/delete', 'Collections#delete', 'delete collection');
        $router->addRoute('post', '/collections/:id/follow', 'Collections#follow', 'follow collection');
        $router->addRoute('post', '/collections/:id/unfollow', 'Collections#unfollow', 'unfollow collection');

        $router->addRoute('get', '/bookmarks', 'Collections#showBookmarks', 'bookmarks');

        // Links
        $router->addRoute('get', '/links/new', 'Links#new', 'new link');
        $router->addRoute('post', '/links/new', 'Links#create', 'create link');
        $router->addRoute('get', '/links/:id', 'Links#show', 'link');
        $router->addRoute('get', '/links/:id/edit', 'Links#edit', 'edit link');
        $router->addRoute('post', '/links/:id/edit', 'Links#update', 'update link');
        $router->addRoute('post', '/links/:id/delete', 'Links#delete', 'delete link');
        $router->addRoute('get', '/links/:id/fetch', 'Links#showFetch', 'show fetch link');
        $router->addRoute('post', '/links/:id/fetch', 'Links#fetch', 'fetch link');
        $router->addRoute('get', '/links/:id/share', 'Links#sharing', 'sharing link');
        $router->addRoute('post', '/links/:id/visibility', 'Links#updateVisibility', 'update link visibility');

        // Link collections
        $router->addRoute('get', '/links/:id/collections', 'LinkCollections#index', 'link collections');
        $router->addRoute('post', '/links/:id/collections', 'LinkCollections#update', 'update link collections');
        $router->addRoute('post', '/links/:id/bookmark', 'LinkCollections#bookmark', 'bookmark link');
        $router->addRoute('post', '/links/:id/unbookmark', 'LinkCollections#unbookmark', 'unbookmark link');

        // Messages
        $router->addRoute('get', '/links/:link_id/messages', 'LinkMessages#index', 'links/messages');
        $router->addRoute('post', '/links/:link_id/messages', 'LinkMessages#create', 'links/create message');

        // This should be used only for source mapping
        $router->addRoute('get', '/src/assets/*', 'Assets#show');

        $router->addRoute('get', '/design', 'Pages#design', 'design');

        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);

        // Initialize the localization
        bindtextdomain('main', utils\Locale::localesPath());
        textdomain('main');
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
        ]);

        \Minz\Output\View::declareDefaultVariables([
            'environment' => \Minz\Configuration::$environment,
            'brand' => \Minz\Configuration::$application['brand'],
            'errors' => $errors,
            'error' => $error,
            'status' => $status,
            'current_action_pointer' => $request->param('_action_pointer'),
            'canonical' => null,
            'available_locales' => utils\Locale::availableLocales(),
            'current_locale' => $locale,
            'current_user' => $current_user,
            'current_tab' => null,
            'styles' => [],
            'javascript_configuration' => json_encode(include('utils/javascript_configuration.php')),
            'no_layout' => $request->header('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest',
            'banner' => true,
            'demo' => \Minz\Configuration::$application['demo'],
            'registrations_opened' => \Minz\Configuration::$application['registrations_opened'],
        ]);

        return $response;
    }
}
