<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<opml version="2.0">
    <head>
        <title><?= $brand ?></title>
        <dateCreated><?= $now->format('D, d M Y H:i:s') ?></dateCreated>
    </head>

    <body>
        <?php $collections = $groups_to_collections[null] ?? []; ?>
        <?php foreach ($collections as $collection): ?>
            <?= $this->include('collections/_collection.opml.xml.php', [
                'collection' => $collection,
            ]) ?>
        <?php endforeach; ?>

        <?php foreach ($groups as $group): ?>
            <outline text="<?= protect($group->name) ?>">
                <?php $collections = $groups_to_collections[$group->id] ?? []; ?>
                <?php foreach ($collections as $collection): ?>
                    <?= $this->include('collections/_collection.opml.xml.php', [
                        'collection' => $collection,
                    ]) ?>
                <?php endforeach; ?>
            </outline>
        <?php endforeach; ?>
    </body>
</opml>
