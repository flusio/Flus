<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<opml version="2.0">
    <head>
        <title><?= $brand ?></title>
        <dateCreated><?= $now->format('D, d M Y H:i:s') ?></dateCreated>
    </head>

    <body>
        <?php $collections = $groups_to_collections[null] ?? []; ?>
        <?php foreach ($collections as $collection): ?>
            <?php if ($collection->type === 'feed'): ?>
                <outline type="rss" text="<?= protect($collection->name) ?>" xmlUrl="<?= protect($collection->feed_url) ?>" htmlUrl="<?= protect($collection->feed_site_url) ?>" />
            <?php else: ?>
                <outline type="rss" text="<?= protect($collection->name) ?>" xmlUrl="<?= url_full('collection feed', ['id' => $collection->id]) ?>" htmlUrl="<?= url_full('collection', ['id' => $collection->id]) ?>" />
            <?php endif; ?>
        <?php endforeach; ?>

        <?php foreach ($groups as $group): ?>
            <outline text="<?= protect($group->name) ?>">
                <?php $collections = $groups_to_collections[$group->id] ?? []; ?>
                <?php foreach ($collections as $collection): ?>
                    <?php if ($collection->type === 'feed'): ?>
                        <outline type="rss" text="<?= protect($collection->name) ?>" xmlUrl="<?= protect($collection->feed_url) ?>" htmlUrl="<?= protect($collection->feed_site_url) ?>" />
                    <?php else: ?>
                        <outline type="rss" text="<?= protect($collection->name) ?>" xmlUrl="<?= url_full('collection feed', ['id' => $collection->id]) ?>" htmlUrl="<?= url_full('collection', ['id' => $collection->id]) ?>" />
                    <?php endif; ?>
                <?php endforeach; ?>
            </outline>
        <?php endforeach; ?>
    </body>
</opml>
