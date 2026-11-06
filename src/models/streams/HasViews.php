<?php

namespace App\models\streams;

use App\models\User;
use App\models\View;

/**
 * Add the relation between a stream and its views.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasViews
{
    /**
     * Return the default view of the stream, or a new unsaved one with the
     * default parameters.
     *
     * The context user is the one who would own the view once saved.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     */
    public function defaultView(array $options = []): View
    {
        $context_user = $options['context_user'] ?? null;
        return $this->memoize(['default_view', $context_user], function () use ($context_user): View {
            return View::findOrBuildDefaultForStream($this, $context_user);
        });
    }

    /**
     * Return the views of the stream, the default one first, then sorted by
     * name.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     *
     * @return View[]
     */
    public function views(array $options = []): array
    {
        $context_user = $options['context_user'] ?? null;
        return $this->memoize(['views', $context_user], function () use ($options): array {
            $views = View::listByStream($this);

            // listByStream() only returns the saved views: the default one has
            // to be added by hand as long as it may have not been saved yet.
            array_unshift($views, $this->defaultView($options));

            return $views;
        });
    }
}
