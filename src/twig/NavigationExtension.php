<?php

namespace App\twig;

use App\navigations;
use Twig\Attribute\AsTwigFunction;

class NavigationExtension
{
    #[AsTwigFunction('reading_navigation')]
    public static function readingNavigation(string $current): navigations\ReadingNavigation
    {
        return new navigations\ReadingNavigation($current);
    }

    #[AsTwigFunction('account_navigation')]
    public static function accountNavigation(string $current): navigations\AccountNavigation
    {
        return new navigations\AccountNavigation($current);
    }
}
