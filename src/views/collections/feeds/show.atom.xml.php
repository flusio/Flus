<?= '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" ?>
<?= '<?xml-stylesheet href="' . url('feeds xsl') . '" type="text/xsl"?>' . "\n" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= protect($collection->name) ?></title>
    <?php if ($collection->description): ?>
        <subtitle type="html"><![CDATA[<?= $collection->descriptionAsHtml() ?>]]></subtitle>
    <?php endif; ?>

    <link href="<?= url_full('collection', ['id' => $collection->id]) ?>" rel="alternate" type="text/html" />
    <link href="<?= url_full('collection feed', ['id' => $collection->id]) ?>" rel="self" type="application/atom+xml" />

    <id><?= $collection->tagUri() ?></id>
    <author>
        <name><?= protect($collection->owner()->username) ?></name>
    </author>
    <generator><?= $user_agent ?></generator>

    <?php if (isset($links[0])): ?>
        <updated><?= $links[0]->published_at->format(\DateTimeInterface::ATOM) ?></updated>
    <?php else: ?>
        <updated><?= \Minz\Time::now()->format(\DateTimeInterface::ATOM) ?></updated>
    <?php endif; ?>

    <?php foreach ($topics as $topic): ?>
        <category term="<?= protect($topic->label) ?>"></category>
    <?php endforeach; ?>

    <?php foreach ($links as $link): ?>
        <entry>
            <title><?= protect($link->title) ?></title>
            <id><?= $link->tagUri() ?></id>

            <?php if ($direct): ?>
                <link href="<?= protect($link->url) ?>" rel="alternate" type="text/html" />
                <link href="<?= url_full('link', ['id' => $link->id]) ?>" rel="replies" type="text/html" />
            <?php else: ?>
                <link href="<?= url_full('link', ['id' => $link->id]) ?>" rel="alternate" type="text/html" />
                <link href="<?= protect($link->url) ?>" rel="via" type="text/html" />
            <?php endif; ?>

            <?php
                $messages = $link->messages();
                if ($messages) {
                    $updated = $messages[count($messages) - 1]->created_at;
                } else {
                    $updated = $link->published_at;
                }
            ?>

            <published><?= $link->published_at->format(\DateTimeInterface::ATOM) ?></published>
            <updated><?= $updated->format(\DateTimeInterface::ATOM) ?></updated>

            <content type="html"><![CDATA[
                <?php foreach ($messages as $message): ?>
                    <div><?= $message->contentAsHtml() ?></div>
                <?php endforeach; ?>

                <p>
                    <?= _f('— “<a href="%s">%s</a>” was published on %s', protect($link->url), protect($link->title), protect($link->host())) ?>
                </p>
            ]]></content>
        </entry>
    <?php endforeach; ?>
</feed>
