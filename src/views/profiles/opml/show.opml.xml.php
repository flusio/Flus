<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<opml version="2.0">
    <head>
        <title><?= protect($user->username) ?></title>
        <dateCreated><?= $now->format('D, d M Y H:i:s') ?></dateCreated>
    </head>

    <body>
        <?php foreach ($collections as $collection): ?>
            <?= $this->include('collections/_collection.opml.xml.php', [
                'collection' => $collection,
            ]) ?>
        <?php endforeach; ?>
    </body>
</opml>
