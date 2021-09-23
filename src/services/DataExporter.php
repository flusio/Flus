<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

class DataExporter
{
    /** @var string */
    private $exportations_path;

    /**
     * @param string $exportations_path
     *
     * @throws RuntimeException if the path doesn't exist or is not a directory
     */
    public function __construct($exportations_path)
    {
        if (!file_exists($exportations_path)) {
            throw new \RuntimeException('The path does not exist');
        }

        if (!is_dir($exportations_path)) {
            throw new \RuntimeException('The path is not a directory');
        }

        $this->exportations_path = $exportations_path;
    }

    /**
     * Export data of the given user.
     *
     * @param string $user_id
     *
     * @throws RuntimeException if the user doesn't exist
     *
     * @return string The path to the data file
     */
    public function export($user_id)
    {
        $user = models\User::find($user_id);
        if (!$user) {
            throw new \RuntimeException('The user does not exist');
        }

        utils\Locale::setCurrentLocale($user->locale);

        $files = [];
        $files['metadata.json'] = $this->generateMetadata();
        $files['followed.opml.xml'] = $this->generateOpml($user);
        $files['bookmarks.atom.xml'] = $this->generateCollection($user->bookmarks());
        $files['news.atom.xml'] = $this->generateCollection($user->news());
        $files['read.atom.xml'] = $this->generateCollection($user->readList());
        $files['never.atom.xml'] = $this->generateCollection($user->neverList());

        $collections = $user->collections(true);
        foreach ($collections as $collection) {
            $files["collections/{$collection->id}.atom.xml"] = $this->generateCollection($collection);
        }

        $links = models\Link::daoToList('listWithCommentsForUser', $user->id);
        foreach ($links as $link) {
            $files["messages/{$link->id}.atom.xml"] = $this->generateLink($link);
        }

        $now = \Minz\Time::now();
        $now_formatted = \Minz\Time::now()->format('Y-m-d_H\hi');
        $filepath = "{$this->exportations_path}/{$now_formatted}_{$user->id}_data.zip";

        $zip_archive = new \ZipArchive();
        $zip_archive->open($filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $filename => $content) {
            $zip_archive->addFromString($filename, $content);
        }

        $zip_archive->close();

        return $filepath;
    }

    /**
     * Return metadata for the archive.
     *
     * @return string
     */
    private function generateMetadata()
    {
        return json_encode([
            'generator' => \Minz\Configuration::$application['user_agent'],
        ]);
    }

    /**
     * Return an OPML representation of the followed collections of the given user.
     *
     * @param \flusio\models\User $user
     *
     * @return string
     */
    private function generateOpml($user)
    {
        $no_group_followed_collections = models\Collection::daoToList('listFollowedInGroup', $user->id, null);
        $groups = models\Group::daoToList('listBy', ['user_id' => $user->id]);

        $view = new \Minz\Output\View('collections/followed.opml.xml', [
            'brand' => \Minz\Configuration::$application['brand'],
            'now' => \Minz\Time::now(),
            'no_group_followed_collections' => $no_group_followed_collections,
            'groups' => $groups,
        ]);

        return $view->render();
    }

    /**
     * Return an Atom representation of the given collection.
     *
     * @param \flusio\models\Collection $collection
     *
     * @return string
     */
    private function generateCollection($collection)
    {
        $view = new \Minz\Output\View('collections/exportation.atom.xml.phtml', [
            'brand' => \Minz\Configuration::$application['brand'],
            'user_agent' => \Minz\Configuration::$application['user_agent'],
            'collection' => $collection,
            'topics' => $collection->topics(),
            'links' => $collection->links(),
        ]);

        return $view->render();
    }

    /**
     * Return an Atom representation of the given link messages.
     *
     * @param \flusio\models\Link $link
     *
     * @return string
     */
    private function generateLink($link)
    {
        $view = new \Minz\Output\View('links/exportation.atom.xml.phtml', [
            'brand' => \Minz\Configuration::$application['brand'],
            'user_agent' => \Minz\Configuration::$application['user_agent'],
            'link' => $link,
            'messages' => $link->messages(),
        ]);

        return $view->render();
    }
}
