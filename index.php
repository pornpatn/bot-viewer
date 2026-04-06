<?php
require_once __DIR__ . '/includes/bootstrap.php';

function format_home_date($timestamp): string
{
    if (!$timestamp || !is_numeric($timestamp)) {
        return '';
    }
    return date('F j, Y', (int)$timestamp);
}

function build_home_article_link(array $article): string
{
    if (!empty($article['alias'])) {
        return 'article.php?alias=' . urlencode($article['alias']);
    }
    return 'article.php?id=' . (int)$article['nid'];
}

function truncate_home_text(?string $text, int $maxLength = 160): string
{
    if (!$text) return '';

    $text = trim(strip_tags($text));
    if ($text === '' || strlen($text) <= $maxLength) {
        return $text;
    }

    $truncated = substr($text, 0, $maxLength);
    $lastSpace = strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }

    return $truncated . '...';
}

$latestArticles = fetch_articles($pdo, 4);
$recentPhotos = fetch_recent_bird_photos($pdo, 8);
$orders = bot_fetch_orders($pdo);

$totalOrders = count($orders);
$totalSpecies = 0;
foreach ($orders as $order) {
    $totalSpecies += (int)($order['species_count'] ?? 0);
}

include 'includes/header.php';
?>

<main class="container page-content home-page">
    <!-- <section class="home-hero">
        <div class="home-hero-copy">
            <div class="eyebrow">Birds of Thailand</div>
            <h1 class="home-title">Bird species, articles, and field photography in one place.</h1>
            <p class="home-intro">
                Explore the bird list by taxonomic order, read the latest articles, and browse recent bird photos from the collection.
            </p>
            <div class="home-actions">
                <a class="home-btn primary" href="birds.php">Browse Birds</a>
                <a class="home-btn" href="articles.php">View Articles</a>
            </div>
        </div>

        <div class="home-hero-card content-card">
            <div class="home-stat">
                <div class="home-stat-value"><?= (int)$totalOrders ?></div>
                <div class="home-stat-label">orders</div>
            </div>
            <div class="home-stat">
                <div class="home-stat-value"><?= (int)$totalSpecies ?></div>
                <div class="home-stat-label">species</div>
            </div>
            <p class="home-stat-note">The bird list page shows species grouped by order, with family breakdowns and photo counts.</p>
        </div>
    </section> -->

    <section class="home-section">
        <div class="section-headline">
            <h2 class="page-heading">Latest Articles</h2>
            <a class="section-link" href="articles.php">See all articles →</a>
        </div>

        <?php if (!$latestArticles): ?>
            <p>No articles found.</p>
        <?php else: ?>
            <div class="article-list home-article-list">
                <?php foreach ($latestArticles as $article): ?>
                    <?php
                    $url = build_home_article_link($article);
                    $image = drupal_original_url($article['image_uri'] ?? null);
                    ?>
                    <a href="<?= h($url) ?>" class="article-list-item">
                        <div class="article-thumb">
                            <?php if ($image): ?>
                                <img src="<?= h($image) ?>" alt="">
                            <?php else: ?>
                                <div class="article-thumb-placeholder">No Image</div>
                            <?php endif; ?>
                        </div>

                        <div class="article-list-content">
                            <h3 class="article-list-title"><?= h($article['title'] ?? '') ?></h3>
                            <div class="article-list-meta"><?= h(format_home_date($article['created'] ?? null)) ?></div>

                            <?php if (!empty($article['body_summary'])): ?>
                                <p class="article-list-summary"><?= h(truncate_home_text($article['body_summary'], 180)) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="home-section">
        <div class="section-headline">
            <h2 class="page-heading">Recent Bird Photos</h2>
        </div>

        <?php if (!$recentPhotos): ?>
            <p>No recent photos found.</p>
        <?php else: ?>
            <div class="recent-photo-grid">
                <?php foreach ($recentPhotos as $photo): ?>
                    <?php
                    $imageUrl = drupal_style_url($photo['image_uri'] ?? null, 'medium')
                        ?: drupal_original_url($photo['image_uri'] ?? null);
                    $birdName = trim((string)($photo['bird_english_names'] ?? ''));
                    if ($birdName === '') {
                        $birdName = trim((string)($photo['bird_thai_names'] ?? ''));
                    }
                    if ($birdName === '') {
                        $birdName = trim((string)($photo['bird_species_names'] ?? ''));
                    }
                    ?>
                    <a class="recent-photo-card" href="photo.php?id=<?= (int)$photo['photo_nid'] ?>">
                        <div class="recent-photo-media">
                            <?php if ($imageUrl): ?>
                                <img src="<?= h($imageUrl) ?>" alt="<?= h($photo['photo_title'] ?? 'Photo') ?>" loading="lazy">
                            <?php else: ?>
                                <div class="article-thumb-placeholder">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="recent-photo-body">
                            <div class="recent-photo-bird"><?= h($birdName !== '' ? $birdName : ($photo['photo_title'] ?? 'Photo')) ?></div>
                            <?php if (!empty($photo['bird_species_names'])): ?>
                                <div class="recent-photo-sci"><em><?= h($photo['bird_species_names']) ?></em></div>
                            <?php endif; ?>
                            <div class="recent-photo-date"><?= h(format_home_date($photo['created'] ?? null)) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="home-section">
        <div class="section-headline">
            <h2 class="page-heading">Browse Birds</h2>
        </div>

        <div class="content-card browse-birds-card">
            <p>
                Open the full bird list to browse species by order and family. Each species links to its detail page with photos, notes, and comments.
            </p>
            <a class="home-btn primary" href="birds.php">Go to Bird List</a>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
