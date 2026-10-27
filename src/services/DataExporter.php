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
        $files['journal.atom.xml'] = $this->generateCollection($user->journal());
        $files['read-later.atom.xml'] = $this->generateSource($user->readLaterSource());
        $files['read.atom.xml'] = $this->generateSource($user->readSource());

        $streams = $user->streams();
        foreach ($streams as $stream) {
            $files["streams/{$stream->id}.json"] = $this->generateStream($stream, $user);
        }

        $collections = $user->collections();
        foreach ($collections as $collection) {
            $files["collections/{$collection->id}.atom.xml"] = $this->generateCollection($collection);
        }

        $links = models\Link::listByUserWithNotes($user);
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
        $opml_generator = new OpmlGenerator();
        return $opml_generator->renderFollows($user, [
            'context_user' => $user,
            'with_time_filter' => true,
        ]);
    }

    /**
     * Return a JSON representation of the given stream.
     *
     * @throws \RuntimeException
     *     If the JSON cannot be generated.
     */
    private function generateStream(models\Stream $stream, models\User $user): string
    {
        $sources = $stream->sources(['context_user' => $user]);

        $source_urls = array_map(function (models\Collection $source): string {
            return $source->feedUrl(direct: true);
        }, $sources);

        $views = array_map(function (models\View $view): array {
            return [
                'name' => $view->name,
                'is_default' => $view->is_default,
                'parameters' => $view->parameters,
            ];
        }, models\View::listByStream($stream));

        $shares = array_map(function (models\StreamShare $share): string {
            return \Minz\Url::absoluteFor('profile', ['id' => $share->user_id]);
        }, $stream->shares());

        $json = json_encode([
            'id' => $stream->id,
            'created_at' => $stream->created_at->format(\DateTimeInterface::ATOM),
            'name' => $stream->name,
            'description' => $stream->description,
            'is_public' => $stream->is_public,
            'display_unread_in_sidenav' => $stream->display_unread_in_sidenav,
            'sources' => $source_urls,
            'views' => $views,
            'shares' => $shares,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!$json) {
            throw new \RuntimeException('Cannot generate the stream file');
        }

        return $json;
    }

    /**
     * Return an Atom representation of the given collection.
     */
    private function generateCollection(models\Collection $collection): string
    {
        $view = new \Minz\Template\Twig('collections/exportation.atom.xml.twig', [
            'collection' => $collection,
            'topics' => $collection->topics(),
            'links' => $collection->links(['published_at']),
        ]);

        return utils\Xml::format($view->render());
    }

    /**
     * Return an Atom representation of the given source.
     */
    private function generateSource(models\Source $source): string
    {
        $view = new \Minz\Template\Twig('sources/exportation.atom.xml.twig', [
            'source' => $source,
            'links' => $source->links(),
        ]);

        return utils\Xml::format($view->render());
    }

    /**
     * Return an Atom representation of the given link notes.
     */
    private function generateLink(models\Link $link): string
    {
        $view = new \Minz\Template\Twig('links/exportation.atom.xml.twig', [
            'link' => $link,
            'notes' => $link->notes(),
        ]);

        return utils\Xml::format($view->render());
    }
}
