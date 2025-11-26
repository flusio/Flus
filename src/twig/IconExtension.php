<?php

namespace App\twig;

use Twig\Attribute\AsTwigFunction;

class IconExtension
{
    #[AsTwigFunction('icon', isSafe: ['html'])]
    public static function icon(string $icon_name, string $additional_class_names = ''): string
    {
        $class = "icon icon--{$icon_name}";
        if ($additional_class_names) {
            $class .= ' ' . $additional_class_names;
        }

        $url_icons = \Minz\Template\TwigExtension::urlStatic('static/icons.svg');
        $svg = "<svg class=\"{$class}\" aria-hidden=\"true\" width=\"36\" height=\"36\">";
        $svg .= "<use xlink:href=\"{$url_icons}#{$icon_name}\"/>";
        $svg .= '</svg>';
        return $svg;
    }
}
