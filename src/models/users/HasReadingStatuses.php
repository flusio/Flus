<?php

namespace App\models\users;

use App\models\Link;
use App\models\ReadLaterList;
use App\models\ReadList;
use App\models\UrlStatus;

/**
 * Add methods to manage the reading statuses of the links for the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasReadingStatuses
{
    /**
     * Return the list of read links of the current user.
     */
    public function readList(): ReadList
    {
        return new ReadList($this);
    }

    /**
     * Return the list of links to read later of the current user.
     */
    public function readLaterList(): ReadLaterList
    {
        return new ReadLaterList($this);
    }

    /**
     * Return whether the user has read the link or not.
     */
    public function hasRead(Link $link): bool
    {
        $url_status = $this->urlStatusOfLink($link);
        return $url_status->isRead();
    }

    /**
     * Return whether the user wants to read the link later or not.
     */
    public function hasReadLater(Link $link): bool
    {
        $url_status = $this->urlStatusOfLink($link);
        return $url_status->isReadLater();
    }

    /**
     * Return whether the user has dismissed the link or not.
     */
    public function hasDismissed(Link $link): bool
    {
        $url_status = $this->urlStatusOfLink($link);
        return $url_status->isDismissed();
    }

    /**
     * Mark the links as read for the user and remove them from their journal.
     *
     * @param Link|Link[] $links
     */
    public function markAsRead(Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        UrlStatus::markAsRead($this, $links);
        $this->unmemoizeUrlStatusesOfLinks($links);

        $this->journal()->removeLinks($links);
    }

    /**
     * Unmark the links as read for the user.
     *
     * @param Link|Link[] $links
     */
    public function unmarkAsRead(Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        UrlStatus::unmarkAsRead($this, $links);
        $this->unmemoizeUrlStatusesOfLinks($links);
    }

    /**
     * Mark the links to read later for the user and remove them from their
     * journal.
     *
     * @param Link|Link[] $links
     */
    public function markAsReadLater(Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        UrlStatus::markAsReadLater($this, $links);
        $this->unmemoizeUrlStatusesOfLinks($links);

        $this->journal()->removeLinks($links);
    }

    /**
     * Mark the links as dismissed for the user and remove them from their
     * journal.
     *
     * @param Link|Link[] $links
     */
    public function markAsDismissed(Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        UrlStatus::markAsDismissed($this, $links);
        $this->unmemoizeUrlStatusesOfLinks($links);

        $this->journal()->removeLinks($links);
    }

    /**
     * Unmark the links for the user (aka remove the corresponding URL statuses).
     *
     * @param Link|Link[] $links
     */
    public function unmark(Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        UrlStatus::unmark($this, $links);
        $this->unmemoizeUrlStatusesOfLinks($links);
    }

    /**
     * Transfer the URL status from a Link to another one.
     *
     * The status of the old Link URL is deleted and its states are copied to
     * the status of the new Link URL. The existing states of the new URL are
     * preserved.
     */
    public function transferUrlStatus(Link $old_link, Link $new_link): void
    {
        $old_url_status = UrlStatus::findOrBuild($this, $old_link->url);

        if (!$old_url_status->isPersisted()) {
            return;
        }

        $new_url_status = UrlStatus::findOrBuild($this, $new_link->url);
        $new_url_status->created_at ??= $old_url_status->created_at;
        $new_url_status->read_at ??= $old_url_status->read_at;
        $new_url_status->read_later_at ??= $old_url_status->read_later_at;
        $new_url_status->dismissed_at ??= $old_url_status->dismissed_at;
        $new_url_status->save();

        $old_url_status->remove();

        $this->unmemoizeUrlStatusesOfLinks([$old_link, $new_link]);
    }

    /**
     * Get the UrlStatus corresponding to this user and link.
     *
     * A UrlStatus is always returned. In case it is unknown to the user, a
     * UrlStatus is built (but not saved in database).
     */
    private function urlStatusOfLink(Link $link): UrlStatus
    {
        return $this->memoize(
            ['url_status', $link->url_hash],
            fn(): UrlStatus => UrlStatus::findOrBuild($this, $link->url)
        );
    }

    /**
     * Set the URL statuses of the user without querying the database.
     *
     * @see \App\models\links\Preloader
     *
     * @param UrlStatus[] $url_statuses
     */
    public function preloadUrlStatuses(array $url_statuses): void
    {
        foreach ($url_statuses as $url_status) {
            $this->memoizeValue(['url_status', $url_status->url_hash], $url_status);
        }
    }

    /**
     * Unmemoize statuses for the links' urls.
     *
     * @param Link[] $links
     */
    private function unmemoizeUrlStatusesOfLinks(array $links): void
    {
        foreach ($links as $link) {
            $this->unmemoize(['url_status', $link->url_hash]);
        }
    }
}
