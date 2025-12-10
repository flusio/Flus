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
        'back' => icon('arrow-left'),
        'check' => icon('check'),
        'error' => icon('error'),
        'eye' => icon('eye'),
        'eye-hide' => icon('eye-hide'),
        'times' => icon('times'),
    ],
];
