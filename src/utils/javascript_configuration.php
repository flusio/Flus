<?php

$translations = [
    '{{count}} characters out of a maximum of {{max}}',
    'Back',
    'Copied',
    'Hide',
    'Open the list',
    ' (public)',
    'Show',
    'The post is too long.',
];

$l10n = [];
foreach ($translations as $translation) {
    $l10n[$translation] = _($translation);
}

return [
    'l10n' => $l10n,
    'icons' => [
        'back' => \App\twig\IconExtension::icon('arrow-left'),
        'check' => \App\twig\IconExtension::icon('check'),
        'error' => \App\twig\IconExtension::icon('error'),
        'eye' => \App\twig\IconExtension::icon('eye'),
        'eye-hide' => \App\twig\IconExtension::icon('eye-hide'),
        'times' => \App\twig\IconExtension::icon('times'),
    ],
];
