<?php

namespace flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InternalPathChecker
{
    /**
     * Verify that the given path matches a real route in the application.
     */
    public function isInternalPath(string $path): bool
    {
        try {
            /** @var \Minz\Router */
            $router = \Minz\Engine::router();
            $router->match('GET', $path);
            return true;
        } catch (\Minz\Errors\RouteNotFoundError $e) {
            return false;
        }
    }
}
