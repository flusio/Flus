<?php

namespace App\services;

use App\models;
use App\utils;

class DataExporter
{
    private string $exportations_path;

    /**
     * @throws \RuntimeException if the path doesn't exist or is not a directory
     */
    public function __construct(string $exportations_path)
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
     * Export data of the given user and return the path to the data file.
     *
     * @throws \RuntimeException
     *     If the user doesn't exist, or if an error happens.
     */
    public function export(string $user_id): string
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

        $collections = $user->collections();
        foreach ($collections as $collection) {
            $files["collections/{$collection->id}.atom.xml"] = $this->generateCollection($collection);
        }

        $links = models\Link::listByUserIdWithNotes($user->id);
        foreach ($links as $link) {
            $files["notes/{$link->id}.atom.xml"] = $this->generateLink($link);
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
     * @throws \RuntimeException
     *     If the metadata cannot be generated.
     */
    private function generateMetadata(): string
    {
        $metadata = json_encode([
            'generator' => utils\UserAgent::get(),
        ]);

        if (!$metadata) {
            throw new \RuntimeException('Cannot generate metada');
        }

        return $metadata;
    }

    /**
     * Return an OPML representation of the followed collections of the given user.
     */
    private function generateOpml(models\User $user): string
    {
        $groups = models\Group::listBy(['user_id' => $user->id]);
        $collections = $user->followedCollections(['time_filter']);
        $groups_to_collections = utils\Grouper::groupBy($collections, 'group_id');

        $view = new \Minz\Template\Simple('collections/followed.opml.xml.php', [
            'brand' => \App\Configuration::$application['brand'],
            'now' => \Minz\Time::now(),
            'groups' => $groups,
            'groups_to_collections' => $groups_to_collections,
        ]);

        return self::formatXML($view->render());
    }

    /**
     * Return an Atom representation of the given collection.
     */
    private function generateCollection(models\Collection $collection): string
    {
        $view = new \Minz\Template\Simple('collections/exportation.atom.xml.php', [
            'brand' => \App\Configuration::$application['brand'],
            'user_agent' => utils\UserAgent::get(),
            'collection' => $collection,
            'topics' => $collection->topics(),
            'links' => $collection->links(['published_at']),
        ]);

        return self::formatXML($view->render());
    }

    /**
     * Return an Atom representation of the given link notes.
     */
    private function generateLink(models\Link $link): string
    {
        $view = new \Minz\Template\Simple('links/exportation.atom.xml.php', [
            'brand' => \App\Configuration::$application['brand'],
            'user_agent' => utils\UserAgent::get(),
            'link' => $link,
            'notes' => $link->notes(),
        ]);

        return self::formatXML($view->render());
    }

    /**
     * Return a formatted version of a XML string.
     *
     * If an error occurs, the initial XML is returned as is.
     */
    private static function formatXML(string $xml_as_string): string
    {
        $dom_document = new \DOMDocument();
        $dom_document->preserveWhiteSpace = false;
        $dom_document->formatOutput = true;
        $result = @$dom_document->loadXML($xml_as_string);
        if (!$result) {
            return $xml_as_string;
        }

        $formatted_xml = $dom_document->saveXML();
        if (!$formatted_xml) {
            return $xml_as_string;
        }

        return $formatted_xml;
    }
}
