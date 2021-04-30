<?php

$translations = [
    'Copied',
    'Unselect',
    'Unselect this collection',
    'Hide',
    'Show',
];

$l10n = [];
foreach ($translations as $translation) {
    $l10n[$translation] = _($translation);
}

return [
    'l10n' => $l10n,
    'icons' => [
        'check' => \flusio\utils\Icon::get('check'),
        'eye' => \flusio\utils\Icon::get('eye'),
        'eye-hide' => \flusio\utils\Icon::get('eye-hide'),
        'times' => \flusio\utils\Icon::get('times'),
    ],
];
