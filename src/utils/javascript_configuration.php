<?php

$translations = [
    'Back',
    'Copied',
    'Hide',
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
        'back' => \flusio\utils\Icon::get('arrow-left'),
        'check' => \flusio\utils\Icon::get('check'),
        'eye' => \flusio\utils\Icon::get('eye'),
        'eye-hide' => \flusio\utils\Icon::get('eye-hide'),
        'times' => \flusio\utils\Icon::get('times'),
    ],
];
