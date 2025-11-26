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

    /**
     * @param 'cards'|'large'|'avatars' $type
     */
    #[AsTwigFunction('url_media')]
    public static function urlMedia(string $type, ?string $filename, string $default = 'default-card.png'): string
    {
        if (!$filename) {
            return \Minz\Template\TwigExtension::urlStatic("static/{$default}");
        }

        $media_path = \App\Configuration::$application['media_path'];
        $subpath = \App\utils\Belt::filenameToSubpath($filename);
        $filepath = "{$media_path}/{$type}/{$subpath}/{$filename}";
        $modification_time = @filemtime($filepath);
        $file_url = \Minz\Url::path() . "/media/{$type}/{$subpath}/{$filename}";
        if ($modification_time) {
            return $file_url . '?' . $modification_time;
        } else {
            return \Minz\Template\TwigExtension::urlStatic("static/{$default}");
        }
    }

    /**
     * @param 'cards'|'large'|'avatars' $type
     */
    #[AsTwigFunction('url_media_full')]
    public static function urlMediaFull(string $type, ?string $filename, string $default = 'default-card.png'): string
    {
        return \Minz\Url::baseUrl() . self::urlMedia($type, $filename, $default);
    }

    #[AsTwigFunction('url_avatar')]
    public static function urlAvatar(?string $filename): string
    {
        return self::urlMedia('avatars', $filename, 'default-avatar.svg');
    }
}
