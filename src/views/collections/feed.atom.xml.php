<?= '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= protect($collection->name) ?></title>
    <?php if ($collection->description): ?>
        <subtitle type="html"><![CDATA[<?= nl2br(protect($collection->description)) ?>]]></subtitle>
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

            <link href="<?= protect($link->url) ?>" rel="alternate" type="text/html" />
            <link href="<?= url_full('link', ['id' => $link->id]) ?>" rel="replies" type="text/html" />

            <published><?= $link->published_at->format(\DateTimeInterface::ATOM) ?></published>
            <updated><?= $link->published_at->format(\DateTimeInterface::ATOM) ?></updated>

            <content type="html"><![CDATA[
                <p><?= _f('“%s”', protect($link->title)) ?></p>
                <p><?= _f('Read this on <a href="%s">%s</a>.', protect($link->url), protect($link->host())) ?></p>
                <p><?= _f('My comments on <a href="%s">%s</a>.', url_full('link', ['id' => $link->id]), $brand) ?></p>
            ]]></content>
        </entry>
    <?php endforeach; ?>
</feed>