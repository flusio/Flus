<?php

$translations = [
    'Add to bookmarks',
    'copied',
    'copy',
    'Unselect',
    'Unselect this collection',
    'Hide',
    'Remove from bookmarks',
    'Show',
];

$l10n = [];
foreach ($translations as $translation) {
    $l10n[$translation] = _($translation);
}

return [
    "l10n" => $l10n,
];
