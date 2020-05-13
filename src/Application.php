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
        // Initialize the routes
        $router = new \Minz\Router();
        $router->addRoute('get', '/', 'Pages#home', 'home');
        $router->addRoute('get', '/about', 'Pages#about', 'about');
        $router->addRoute('post', '/sessions/locale', 'Sessions#changeLocale', 'change locale');

        // Registration
        $router->addRoute('get', '/registration', 'Users#registration', 'registration');
        $router->addRoute('post', '/registration', 'Users#create', 'create user');
        $router->addRoute('get', '/registration/validation', 'Users#validation', 'registration validation');

        // This should be used only for source mapping
        $router->addRoute('get', '/src/assets/*', 'Assets#show');

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
        if (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } else {
            $locale = utils\Locale::DEFAULT_LOCALE;
        }
        utils\Locale::setCurrentLocale($locale);

        \Minz\Output\View::declareDefaultVariables([
            'environment' => \Minz\Configuration::$environment,
            'errors' => [],
            'error' => null,
            'current_locale' => $locale,
            'current_user' => $current_user,
        ]);

        return $this->engine->run($request);
    }
}
