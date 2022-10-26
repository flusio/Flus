<?php

$translations = [
    'Back',
    'Copied',
    'Hide',
    'Open the list',
    'public',
    ' (public)',
    'Show',
    'Unselect',
    'Unselect this collection',
];

$l10n = [];
foreach ($translations as $translation) {
    $l10n[$translation] = _($translation);
}

return [
    'l10n' => $l10n,
    'icons' => [
        'back' => icon('arrow-left'),
        'check' => icon('check'),
        'eye' => icon('eye'),
        'eye-hide' => icon('eye-hide'),
        'times' => icon('times'),
    ],
];
