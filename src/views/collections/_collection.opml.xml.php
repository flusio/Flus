<?php if ($collection->type === 'feed'): ?>
    <outline
        type="rss"
        text="<?= protect($collection->name) ?>"
        xmlUrl="<?= protect($collection->feed_url) ?>"
        htmlUrl="<?= protect($collection->feed_site_url) ?>"
        <?php if ($collection->time_filter !== 'normal'): ?>
            category="/flusio/filters/<?= $collection->time_filter ?>"
        <?php endif; ?>
    />
<?php else: ?>
    <outline
        type="rss"
        text="<?= protect($collection->name) ?>"
        xmlUrl="<?= url_full('collection feed', ['id' => $collection->id]) ?>"
        htmlUrl="<?= url_full('collection', ['id' => $collection->id]) ?>"
        <?php if ($collection->time_filter !== 'normal'): ?>
            category="/flusio/filters/<?= $collection->time_filter ?>"
        <?php endif; ?>
    />
<?php endif; ?>
