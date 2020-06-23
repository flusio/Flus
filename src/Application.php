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
        $router->addRoute('get', '/about', 'Pages#about', 'about');

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
        $router->addRoute('get', '/account/deletion', 'Accounts#deletion', 'user deletion');
        $router->addRoute('post', '/account/deletion', 'Accounts#delete', 'delete user');

        // Collections
        $router->addRoute('get', '/bookmarks', 'Collections#showBookmarked', 'bookmarks');
        $router->addRoute('post', '/bookmarks', 'Collections#createBookmarked', 'create bookmarks');

        // Links
        $router->addRoute('post', '/links', 'Links#add', 'add link');
        $router->addRoute('get', '/links/:id', 'Links#show', 'show link');
        $router->addRoute('get', '/links/:id/edit', 'Links#showUpdate', 'show update link');
        $router->addRoute('post', '/links/:id/edit', 'Links#update', 'update link');
        $router->addRoute('get', '/links/:id/fetch', 'Links#showFetch', 'show fetch link');
        $router->addRoute('post', '/links/:id/fetch', 'Links#fetch', 'fetch link');
        $router->addRoute(
            'post',
            '/links/:id/remove_collection',
            'Links#removeCollection',
            'remove link collection'
        );

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
        $current_user = utils\CurrentUser::get();

        // Setup current localization
        if ($current_user) {
            $locale = $current_user->locale;
        } elseif (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } else {
            $locale = utils\Locale::DEFAULT_LOCALE;
        }
        utils\Locale::setCurrentLocale($locale);

        $errors = utils\Flash::pop('errors', []);
        $error = utils\Flash::pop('error');
        $status = utils\Flash::pop('status');

        $response = $this->engine->run($request);

        \Minz\Output\View::declareDefaultVariables([
            'environment' => \Minz\Configuration::$environment,
            'errors' => $errors,
            'error' => $error,
            'status' => $status,
            'current_action_pointer' => $request->param('_action_pointer'),
            'available_locales' => utils\Locale::availableLocales(),
            'current_locale' => $locale,
            'current_user' => $current_user,
            'styles' => [],
            'javascript_configuration' => json_encode(include_once('utils/javascript_configuration.php')),
        ]);

        return $response;
    }
}
