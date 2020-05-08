<?php

/**
 * Configure the autoload of the flusio application and its dependencies.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

spl_autoload_register(
    function ($class_name) {
        $app_name = 'flusio';
        $app_path = __DIR__;
        $lib_path = $app_path . '/lib';

        if (strpos($class_name, 'Minz') === 0) {
            include $lib_path . '/Minz/autoload.php';
        } elseif (strpos($class_name, $app_name) === 0) {
            $class_name = substr($class_name, strlen($app_name) + 1);
            $class_path = str_replace('\\', '/', $class_name) . '.php';
            include $app_path . '/src/' . $class_path;
        }
    }
);
