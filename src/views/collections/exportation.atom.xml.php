<?= '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= protect($collection->name) ?></title>
    <?php if ($collection->description): ?>
        <subtitle type="text"><![CDATA[<?= $collection->description ?>]]></subtitle>
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
        <category term="<?= protect($topic->label) ?>" />
    <?php endforeach; ?>

    <category term="Flus:type:<?= $collection->type ?>" />

    <?php if ($collection->is_public): ?>
        <category term="Flus:public" />
    <?php endif; ?>

    <?php if ($collection->group_id): ?>
        <category term="Flus:group" label="<?= protect($collection->groupForUser($collection->user_id)->name) ?>" />
    <?php endif; ?>

    <?php foreach ($links as $link): ?>
        <entry>
            <title><?= protect($link->title) ?></title>
            <id><?= $link->tagUri() ?></id>

            <link href="<?= protect($link->url) ?>" rel="alternate" type="text/html" />
            <link href="<?= url_full('link', ['id' => $link->id]) ?>" rel="replies" type="text/html" />

            <?php if ($link->is_hidden): ?>
                <category term="Flus:hidden" />
            <?php endif; ?>

            <published><?= $link->published_at->format(\DateTimeInterface::ATOM) ?></published>
            <updated><?= $link->published_at->format(\DateTimeInterface::ATOM) ?></updated>

            <content type="html"></content>
        </entry>
    <?php endforeach; ?>
</feed>
