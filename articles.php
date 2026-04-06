<?php

require_once __DIR__ . '/includes/bootstrap.php';

function format_article_date($timestamp)
{
    if (!$timestamp || !is_numeric($timestamp)) {
        return '';
    }

    return date('M j, Y', (int) $timestamp);
}

function build_article_link($article)
{
    if (!empty($article['alias'])) {
        return 'article.php?alias=' . urlencode($article['alias']);
    }

    return 'article.php?id=' . (int)$article['nid'];
}

function truncate_text($text, $maxLength = 160)
{
    if (!$text) return '';

    // remove HTML
    $text = trim(strip_tags($text));

    if (strlen($text) <= $maxLength) {
        return $text;
    }

    // cut text
    $truncated = substr($text, 0, $maxLength);

    // avoid cutting mid-word
    $lastSpace = strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }

    return $truncated . '...';
}

$articles = fetch_articles($pdo, 1000);

include "includes/header.php";
?>

<div class="page-topbar">
    <div class="container page-topbar-inner">
        <a href="index.php" class="back-link">&larr; Back to home</a>
    </div>
</div>

<main class="container page-content">

    <h1 class="page-heading">Articles</h1>

    <?php if (!$articles): ?>
        <p>No articles found.</p>
    <?php else: ?>
        <div class="article-list">

            <?php foreach ($articles as $a): 
                $url = build_article_link($a);
                $image = drupal_original_url($a['image_uri'] ?? null);
            ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="article-list-item">

                    <div class="article-thumb">
                        <?php if ($image): ?>
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="">
                        <?php else: ?>
                            <div class="article-thumb-placeholder">No Image</div>
                        <?php endif; ?>
                    </div>

                    <div class="article-list-content">
                        <h2 class="article-list-title">
                            <?php echo htmlspecialchars($a['title']); ?>
                        </h2>

                        <div class="article-list-meta">
                            <?php echo htmlspecialchars(format_article_date($a['created'])); ?>
                        </div>

                        <?php if (!empty($a['body_summary'])): ?>
                            <p class="article-list-summary">
                                <?php echo htmlspecialchars(truncate_text($a['body_summary'], 1000)); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                </a>
            <?php endforeach; ?>

        </div>
    <?php endif; ?>

</main>

<?php include "includes/footer.php"; ?>