<?php

spl_autoload_register(function ($class_name): void {
    if (str_starts_with($class_name, 'SpiderBits')) {
        $class_name = substr($class_name, 11);
        include(__DIR__ . '/src/' . str_replace('\\', '/', $class_name) . '.php');
    }
});
