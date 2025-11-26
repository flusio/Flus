<?php

namespace App\twig;

use Twig\Attribute\AsTwigFunction;

class ConfigurationExtension
{
    #[AsTwigFunction('app_configuration')]
    public static function appConfiguration(string $key): mixed
    {
        return \App\Configuration::$application[$key] ?? '';
    }

    #[AsTwigFunction('js_configuration', isSafe: ['html'])]
    public static function jsConfiguration(): string
    {
        $configuration_path = \App\Configuration::$app_path . '/src/utils/javascript_configuration.php';
        $configuration = json_encode(include($configuration_path));

        if ($configuration === false) {
            throw new \LogicException('Cannot JSON encode JavaScript configuration.');
        }

        return $configuration;
    }

    #[AsTwigFunction('current_host')]
    public static function currentHost(): string
    {
        return \App\Configuration::$url_options['host'];
    }
}
