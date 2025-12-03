<?php

namespace App\cli;

use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Wallabag
{
    /**
     * @request_param string user
     * @request_param string file
     * @request_param bool import-read-later
     */
    public function import(Request $request): Response
    {
        $user_id = $request->parameters->getString('user', '');
        $filename = $request->parameters->getString('file', '');
        $import_read_later = $request->parameters->getBoolean('import-read-later');

        $user = models\User::find($user_id);

        if (!$user) {
            return Response::text(400, 'User does not exist.');
        }

        utils\Locale::setCurrentLocale($user->locale);

        $content = file_get_contents(getcwd() . '/' . $filename);

        if ($content === false) {
            return Response::text(400, 'Cannot open the file.');
        }

        $items = json_decode($content, associative: true);

        if (!is_array($items)) {
            return Response::text(400, 'Invalid file content.');
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'title' => $item['title'] ?? '',
                'url' => $item['url'] ?? '',
                'created_at' => $item['created_at'] ?? '',
                'tags' => $item['tags'] ?? [],
                'is_archived' => $item['is_archived'] ?? false,
                'is_public' => $item['is_public'] ?? false,
            ];
        }

        $this->importWallabagItems($user, $items, $import_read_later);

        return Response::text(200, 'Importation finished.');
    }

    /**
     * @param array<array{
     *     title: string,
     *     url: string,
     *     created_at: string,
     *     tags: string[],
     *     is_archived: string,
     *     is_public: bool,
     * }> $items
     */
    public function importWallabagItems(models\User $user, array $items, bool $import_read_later): void
    {
        $bookmarks_collection = $user->bookmarks();
        $read_list_collection = $user->readList();
        $wallabag_collection = models\Collection::findOrCreateBy([
            'name' => _('wallabag links'),
            'user_id' => $user->id,
        ], [
            'id' => \Minz\Random::timebased(),
            'description' => _('All your links imported from wallabag.'),
        ]);

        // This will be used to check if URL has already been added by the user
        $link_ids_by_urls = models\Link::listUrlsToIdsByUserId($user->id);

        // This will store the items that we effectively need to create. We
        // don't create links, collections and their relation on the fly because
        // it would be too intensive. We rather prefer to insert them all at
        // once (see calls to `bulkInsert` below).
        $links_to_create = [];
        $links_to_collections_to_create = [];
        $notes = [];

        foreach ($items as $item) {
            $url = $item['url'];

            if (!\SpiderBits\Url::isValid($url)) {
                continue;
            }

            $collection_ids = [];
            $collection_ids[] = $wallabag_collection->id;
            if ($item['is_archived']) {
                $collection_ids[] = $read_list_collection->id;
            } elseif ($import_read_later) {
                $collection_ids[] = $bookmarks_collection->id;
            }

            $tags = array_map(function (string $tag): string {
                $tag = trim($tag);
                $tag = str_replace(' ', '_', $tag);
                return $tag;
            }, $item['tags']);
            $tags = array_filter($tags);

            if (isset($link_ids_by_urls[$url])) {
                $link_id = $link_ids_by_urls[$url];
            } else {
                // The user didn't added this link yet, so we'll need to create
                // it. First, initiate a new model
                $link = new models\Link($url, $user->id, is_hidden: !$item['is_public']);

                if ($item['title']) {
                    $link->title = $item['title'];
                }

                $link->setTags($tags);

                // In normal cases, created_at is set on save() call. Since we
                // add links via the bulkInsert call, we have to set created_at
                // first, or it would fail because of the not-null constraint.
                $link->created_at = \Minz\Time::now();

                $links_to_create[] = $link;

                $link_ids_by_urls[$link->url] = $link->id;
                $link_id = $link->id;
            }

            $created_at = $item['created_at'];

            $published_at = \DateTimeImmutable::createFromFormat(DATE_ATOM, $created_at);

            if (!$published_at) {
                $published_at = \Minz\Time::now();
            }

            // We now have a link_id and a list of collection ids. We store
            // here the relations in the last _to_create array.
            foreach ($collection_ids as $collection_id) {
                $link_to_collection = new models\LinkToCollection($link_id, $collection_id);
                $link_to_collection->created_at = $published_at;
                $links_to_collections_to_create[] = $link_to_collection;
            }

            // We create a note containing the list of tags if any.
            if (count($tags) > 0) {
                $formatted_tags = array_map(function (string $tag): string {
                    return "#{$tag}";
                }, $tags);

                $content = implode(' ', $formatted_tags);

                $note = new models\Note($user->id, $link_id, $content);
                $note->created_at = $published_at;

                $notes[] = $note;
            }
        }

        // Finally, let the big import (in DB) begin!
        models\Link::bulkInsert($links_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);
        models\Note::bulkInsert($notes);

        // Delete the collections if they are empty at the end of the
        // importation.
        $count_wallabag_links = models\Link::countByCollectionId($wallabag_collection->id);
        if ($count_wallabag_links === 0) {
            $wallabag_collection->remove();
        } else {
            $wallabag_collection->syncPublicationFrequencyPerYear();
            $wallabag_collection->save();
        }

        $bookmarks_collection->syncPublicationFrequencyPerYear();
        $bookmarks_collection->save();
    }
}
