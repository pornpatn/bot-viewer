<?php

require_once __DIR__ . '/includes/bootstrap.php';

function format_article_date($timestamp)
{
    if (!$timestamp || !is_numeric($timestamp)) {
        return '';
    }

    return date('F j, Y', (int) $timestamp);
}

function render_body_html($html)
{
    if (!$html) {
        return '<p>No content available.</p>';
    }

    return $html;
}

function split_tag_names($tag_names)
{
    if (!$tag_names) {
        return [];
    }

    $items = explode(' | ', $tag_names);
    $items = array_map('trim', $items);
    $items = array_filter($items);

    return array_values(array_unique($items));
}

$article = null;
$images = [];
$document_url = null;
$tags = [];

if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $article = fetch_article_by_nid($pdo, (int) $_GET['id']);
} elseif (!empty($_GET['alias'])) {
    $article = fetch_article_by_alias($pdo, trim($_GET['alias']));
}

$page_title = $article['title'] ?? 'Article';

if ($article) {
    $tags = split_tag_names($article['tag_names'] ?? '');
    $document_url = drupal_original_url($article['document_uri'] ?? null);

    $images = fetch_article_images($pdo, $article['nid']);

    foreach ($images as &$image) {
        $image['original_url'] = drupal_original_url($image['uri'] ?? null);
        $image['large_url'] = drupal_style_url($image['uri'] ?? null, 'large') ?: $image['original_url'];
    }
    unset($image);
}

include "includes/header.php";
?>

<div class="page-topbar">
    <div class="container page-topbar-inner">
        <a href="articles.php" class="back-link">&larr; Back to articles</a>
    </div>
</div>

<main class="container page-content">
    <?php if (!$article): ?>
        <section class="article-card">
            <h1 class="article-title">Article not found</h1>
            <p>The article you requested could not be found.</p>
        </section>
    <?php else: ?>
        <article class="article-card">
            <header class="article-header">
                <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>

                <div class="article-meta">
                    <?php if (!empty($article['created'])): ?>
                        <span>Published: <?php echo htmlspecialchars(format_article_date($article['created'])); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($article['changed']) && $article['changed'] != $article['created']): ?>
                        <span>Updated: <?php echo htmlspecialchars(format_article_date($article['changed'])); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($tags): ?>
                    <div class="article-tags">
                        <?php foreach ($tags as $tag): ?>
                            <span class="article-tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ($images): ?>
                <?php if (count($images) === 1): ?>
                    <?php $image = $images[0]; ?>
                    <?php if (!empty($image['large_url'])): ?>
                        <div class="article-image-wrap">
                            <img
                                src="<?php echo htmlspecialchars($image['large_url']); ?>"
                                alt="<?php echo htmlspecialchars($image['field_image_alt'] ?: $image['field_image_title'] ?: $article['title']); ?>"
                                class="article-image"
                            >
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="article-gallery">
                        <?php foreach ($images as $image): ?>
                            <?php if (!empty($image['large_url'])): ?>
                                <a
                                    href="<?php echo htmlspecialchars($image['original_url'] ?: $image['large_url']); ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="article-gallery-item"
                                >
                                    <img
                                        src="<?php echo htmlspecialchars($image['large_url']); ?>"
                                        alt="<?php echo htmlspecialchars($image['field_image_alt'] ?: $image['field_image_title'] ?: $article['title']); ?>"
                                        class="article-gallery-image"
                                    >
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="article-body">
                <?php echo render_body_html($article['body_value'] ?? ''); ?>
            </div>

            <?php if ($document_url): ?>
                <div class="article-document">
                    <h2 class="article-section-title">Document</h2>
                    <p>
                        <a href="<?php echo htmlspecialchars($document_url); ?>" target="_blank" rel="noopener">
                            <?php
                            echo htmlspecialchars(
                                $article['field_document_description']
                                ?: $article['document_filename']
                                ?: 'Download document'
                            );
                            ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</main>

<?php include "includes/footer.php"; ?>