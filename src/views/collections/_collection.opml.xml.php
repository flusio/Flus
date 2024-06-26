<?php
    $display_time_filter = $collection->time_filter && $collection->time_filter !== 'normal';
?>

<?php if ($collection->type === 'feed'): ?>
    <outline
        type="rss"
        text="<?= protect($collection->name) ?>"
        xmlUrl="<?= protect($collection->feed_url) ?>"
        htmlUrl="<?= protect($collection->feed_site_url) ?>"
        <?php if ($display_time_filter): ?>
            category="/Flus/filters/<?= $collection->time_filter ?>"
        <?php endif; ?>
    />
<?php else: ?>
    <outline
        type="rss"
        text="<?= protect($collection->name) ?>"
        xmlUrl="<?= url_full('collection feed', ['id' => $collection->id, 'direct' => 'true']) ?>"
        htmlUrl="<?= url_full('collection', ['id' => $collection->id]) ?>"
        <?php if ($display_time_filter): ?>
            category="/Flus/filters/<?= $collection->time_filter ?>"
        <?php endif; ?>
    />
<?php endif; ?>
