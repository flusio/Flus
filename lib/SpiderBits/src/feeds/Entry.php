<?php

namespace SpiderBits\feeds;

/**
 * An Entry is a generic object to abstract Atom entries and RSS items.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Entry
{
    public string $id = '';

    public string $title = '';

    public string $link = '';

    /** @var string[] */
    public array $links = [];

    /** @var string[] */
    public array $categories = [];

    public ?\DateTimeImmutable $published_at = null;

    public string $content_type = 'text';

    public string $content = ''; // Warning: content is not suitable to be displayed, it should be sanitized first!
}
