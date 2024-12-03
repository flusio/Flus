<?= '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" ?>
<?= '<?xml-stylesheet href="' . url('feeds xsl') . '" type="text/xsl"?>' . "\n" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?= protect($user->username) ?> (<?= get_app_configuration('brand') ?>)</title>

    <link href="<?= url_full('profile', ['id' => $user->id]) ?>" rel="alternate" type="text/html" />
    <link href="<?= url_full('profile feed', ['id' => $user->id]) ?>" rel="self" type="application/atom+xml" />

    <id><?= $user->tagUri() ?></id>
    <author>
        <name><?= protect($user->username) ?></name>
    </author>
    <generator><?= $user_agent ?></generator>

    <?php if (isset($links[0])): ?>
        <updated><?= $links[0]->published_at->format(\DateTimeInterface::ATOM) ?></updated>
    <?php else: ?>
        <updated><?= \Minz\Time::now()->format(\DateTimeInterface::ATOM) ?></updated>
    <?php endif; ?>

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
