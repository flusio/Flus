<?php

namespace tests;

use App\utils;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FilesystemHelper
{
    /**
     * Clear cache and media directories after each test.
     */
    #[\PHPUnit\Framework\Attributes\After]
    public function clearFilesystem(): void
    {
        $cache_path = \App\Configuration::$application['cache_path'];
        utils\FilesystemHelper::recursiveUnlink($cache_path, keep_current: true);

        $media_path = \App\Configuration::$application['media_path'];
        utils\FilesystemHelper::recursiveUnlink($media_path, keep_current: true);
    }
}
