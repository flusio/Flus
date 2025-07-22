<?= '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= protect($link->title) ?></title>
    <link href="<?= url_full('link', ['id' => $link->id]) ?>" rel="alternate" type="text/html" />
    <link href="<?= url_full('link feed', ['id' => $link->id]) ?>" rel="self" type="application/atom+xml" />
    <link href="<?= protect($link->url) ?>" rel="via" type="text/html" />

    <?php if ($link->is_hidden): ?>
        <category term="Flus:hidden" />
    <?php endif; ?>

    <id><?= $link->tagUri() ?></id>
    <author>
        <name><?= protect($link->owner()->username) ?></name>
    </author>
    <generator><?= $user_agent ?></generator>
    <?php if (isset($notes[0])): ?>
        <updated><?= $notes[0]->created_at->format(\DateTimeInterface::ATOM) ?></updated>
    <?php else: ?>
        <updated><?= \Minz\Time::now()->format(\DateTimeInterface::ATOM) ?></updated>
    <?php endif; ?>

    <?php foreach ($notes as $note): ?>
        <?php $user = $note->user(); ?>
        <entry>
            <title><?= _f('Notes by %s', protect($user->username)) ?></title>
            <id><?= $note->tagUri() ?></id>
            <link href="<?= url_full('link', ['id' => $link->id]) ?>#note-<?= $note->id ?>" rel="alternate" type="text/html" />
            <author>
                <name><?= protect($user->username) ?></name>
            </author>
            <published><?= $note->created_at->format(\DateTimeInterface::ATOM) ?></published>
            <updated><?= $note->created_at->format(\DateTimeInterface::ATOM) ?></updated>
            <content type="text"><![CDATA[<?= $note->content ?>]]></content>
        </entry>
    <?php endforeach; ?>
</feed>
