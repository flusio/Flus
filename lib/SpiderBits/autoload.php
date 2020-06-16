<?php

spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'SpiderBits') === 0) {
        $class_name = substr($class_name, 11);
        include(__DIR__ . '/src/' . str_replace('\\', '/', $class_name) . '.php');
    }
});
