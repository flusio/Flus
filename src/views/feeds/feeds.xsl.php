<xsl:stylesheet version="3.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
            <head>
                <title><xsl:value-of select="/atom:feed/atom:title"/> <?= _('(feed)') ?></title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <meta name="viewport" content="width=device-width,initial-scale=1"/>
                <link rel="stylesheet" href="<?= url_asset('stylesheets/application.css') ?>"/>
            </head>
            <body>
                <div class="layout layout--not-connected">
                    <main class="layout__main">
                        <div class="layout__content">
                            <section class="section section--small">
                                <header class="section__title">
                                    <h1>
                                        <?= icon('feed') ?>
                                        <xsl:value-of select="/atom:feed/atom:title"/>
                                        <?= _('(feed)') ?>
                                    </h1>
                                </header>

                                <p class="section__intro">
                                    <?= _('<strong>This is an Atom feed.</strong> You can follow its publications by adding its <abbr>URL</abbr> to your feed aggregator.') ?>
                                </p>

                                <p class="paragraph--featured">
                                    <a class="anchor--action">
                                        <xsl:attribute name="href">
                                            <xsl:value-of select="/atom:feed/atom:link[@rel='alternate']/@href"/>
                                        </xsl:attribute>
                                        <?= _f('Follow on %s', $brand) ?>
                                    </a>
                                </p>
                            </section>

                            <section class="feed section section--small section--longbottom">
                                <h2><?= _('Feed overview') ?></h2>

                                <xsl:for-each select="/atom:feed/atom:entry">
                                    <article class="feed__entry">
                                        <h3>
                                            <xsl:value-of select="atom:title" disable-output-escaping="yes"/>
                                        </h3>

                                        <small>
                                            <?= _('Published on:') ?> <xsl:value-of select="substring(atom:updated,1,10)" />
                                            —
                                            <a target="_blank" rel="noopener noreferrer">
                                                <xsl:attribute name="href">
                                                    <xsl:value-of select="atom:link/@href"/>
                                                </xsl:attribute>
                                                <?= _('Read on the website') ?>
                                            </a>
                                        </small>
                                    </article>
                                </xsl:for-each>
                            </section>
                        </div>
                    </main>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
