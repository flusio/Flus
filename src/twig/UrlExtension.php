<?php

namespace App\twig;

use Twig\Attribute\AsTwigFunction;

class UrlExtension
{
    #[AsTwigFunction('url_asset')]
    public static function urlAsset(string $filename): string
    {
        if (\App\Configuration::isEnvironment('development')) {
            $assets_folder = 'dev_assets';
        } else {
            $assets_folder = 'assets';
        }

        $filepath = \App\Configuration::$app_path . "/public/{$assets_folder}/{$filename}";
        $modification_time = @filemtime($filepath);

        $file_url = \Minz\Url::path() . "/{$assets_folder}/{$filename}";
        if ($modification_time) {
            return $file_url . '?' . $modification_time;
        } else {
            return $file_url;
        }
    }
}
