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

if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $article = fetch_article_by_nid($pdo, (int) $_GET['id']);
} elseif (!empty($_GET['alias'])) {
    $article = fetch_article_by_alias($pdo, trim($_GET['alias']));
}

$page_title = $article['title'] ?? 'Article';

$image_url = null;
$document_url = null;
$tags = [];

if ($article) {
    // ✅ Use your shared helper
    $image_url = drupal_original_url($article['image_uri'] ?? null);
    $document_url = drupal_original_url($article['document_uri'] ?? null);

    $tags = split_tag_names($article['tag_names'] ?? '');
}

include "includes/header.php";
?>

<div class="page-topbar">
    <div class="container page-topbar-inner">
        <a href="index.php" class="back-link">&larr; Back to home</a>
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

            <?php if ($image_url): ?>
                <div class="article-image-wrap">
                    <img
                        src="<?php echo htmlspecialchars($image_url); ?>"
                        alt="<?php echo htmlspecialchars($article['field_image_alt'] ?? $article['title']); ?>"
                        class="article-image"
                    >
                </div>
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