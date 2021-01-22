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
    "l10n" => $l10n,
];
