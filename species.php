<?php
require_once __DIR__ . '/includes/bootstrap.php';

$nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($nid <= 0) die("Invalid species id.");

$species = fetch_bird_detail($pdo, $nid);
if (!$species) die("Species not found.");

// Photos (grouped per photo node, with images[] containing file_uri)
$photosGrouped = fetch_photos_for_bird($pdo, $nid);
$photoCount = count($photosGrouped);

// ---------- Small UI helpers ----------
function render_meta_row(string $label, ?string $value): void
{
    $value = trim((string)$value);
    if ($value === '') return;
?>
    <div class="meta-row">
        <div class="meta-label"><?= h($label) ?></div>
        <div class="meta-value"><?= h($value) ?></div>
    </div>
<?php
}

function render_detail_row(string $label, ?string $value): void
{
    $value = trim((string)$value);
    if ($value === '') return;
?>
    <div class="detail-row">
        <div class="detail-label"><?= h($label) ?></div>
        <div class="detail-value"><?= nl2br(h($value)) ?></div>
    </div>
<?php
}

include "includes/header.php";

// Pull fields (skip empties; no "-")
$title = trim((string)($species['title'] ?? 'Species'));
$english = trim((string)($species['english_names'] ?? ''));
$thai = trim((string)($species['thai_names'] ?? ''));
$sciName = trim((string)($species['species_names'] ?? ''));

$sciOrder = trim((string)($species['sci_orders'] ?? ''));
$sciFamily = trim((string)($species['sci_families'] ?? ''));
$taxonOrder = trim((string)($species['taxon_order'] ?? ''));

$conservation = trim((string)($species['conservation_statuses'] ?? ''));
$seasonal = trim((string)($species['seasonal_statuses'] ?? ''));
$distribution = trim((string)($species['distributions'] ?? ''));
$thConservation = trim((string)($species['th_conversation_statuses'] ?? ''));

$race = trim((string)($species['race_values'] ?? ''));
$size = trim((string)($species['size_values'] ?? ''));

$habitat = trim((string)($species['habitat_values'] ?? ''));
$notes = trim((string)($species['notes_values'] ?? ''));
$taxonomyText = trim((string)($species['taxonomy_text_values'] ?? ''));
?>

<div class="container">
    <div class="page-topbar">
        <a class="back-link" href="index.php">← Back to Home</a>
    </div>

    <!-- TOP: Species Detail -->
    <div class="species-header">
        <div class="species-main">
            <h1 class="species-title"><?= h($title) ?></h1>

            <?php if ($english !== '' || $thai !== ''): ?>
                <div class="species-subtitle">
                    <?php if ($english !== ''): ?><?= h($english) ?><?php endif; ?>
                    <?php if ($english !== '' && $thai !== ''): ?> • <?php endif; ?>
                    <?php if ($thai !== ''): ?><?= h($thai) ?><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($sciName !== ''): ?>
                <div class="species-sciname"><em><?= h($sciName) ?></em></div>
            <?php endif; ?>
        </div>

        <!-- Right-side meta box -->
        <div class="species-meta">
            <?php render_meta_row("Order", $sciOrder); ?>
            <?php render_meta_row("Family", $sciFamily); ?>
        </div>
    </div>

    <!-- DETAILS (new fields) -->
    <?php
    $hasDetails =
        $race !== '' ||
        $size !== '' ||
        $thConservation !== '' ||
        $distribution !== '' ||
        $seasonal !== '' ||
        $conservation !== '';
    ?>

    <?php if ($hasDetails): ?>
        <div class="section">
            <h2>Details</h2>
            <div class="content-card">
                <div class="detail-grid">
                    <?php render_detail_row("Conservation Status", $conservation); ?>
                    <?php render_detail_row("Thai Conservation Status", $thConservation); ?>
                    <?php render_detail_row("Seasonal Status", $seasonal); ?>
                    <?php render_detail_row("Distribution", $distribution); ?>
                    <?php render_detail_row("Race", $race); ?>
                    <?php render_detail_row("Size", $size); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($habitat !== ''): ?>
        <div class="section">
            <h2>Habitat</h2>
            <div class="content-card">
                <?= nl2br(h($habitat)) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($notes !== ''): ?>
        <div class="section">
            <h2>Notes</h2>
            <div class="content-card">
                <?= render_safe_note($notes) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($taxonomyText !== ''): ?>
        <div class="section">
            <h2>Taxonomy</h2>
            <div class="content-card">
                <?= nl2br(h($taxonomyText)) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- COMMENTS -->
    <?php render_comments_section($pdo, $nid); ?>

    <!-- BOTTOM: Photos Grid -->
    <div class="section">
        <div class="section-header">
            <h2>Photos</h2>
            <div class="section-subtitle">
                <?= $photoCount ?> photo<?= $photoCount === 1 ? "" : "s" ?>
            </div>
        </div>

        <?php if ($photoCount === 0): ?>
            <div class="empty-state">No photos available yet.</div>
        <?php else: ?>
            <div class="photo-grid">
                <?php foreach ($photosGrouped as $p): ?>
                    <?php
                    // Use first image (delta 0) for grid thumbnail
                    $firstImg = null;
                    if (!empty($p['images']) && is_array($p['images'])) {
                        // images already ordered by delta ASC in SQL, but keep safe:
                        usort($p['images'], fn($a, $b) => ((int)($a['delta'] ?? 0)) <=> ((int)($b['delta'] ?? 0)));
                        $firstImg = $p['images'][0] ?? null;
                    }

                    $fileUri = $firstImg['file_uri'] ?? null;

                    $thumbUrl = drupal_style_url($fileUri, 'thumbnail');
                    $largeUrl = drupal_style_url($fileUri, 'large');
                    $origUrl  = drupal_original_url($fileUri);

                    $imgForGrid   = $thumbUrl ?: ($origUrl ?: null);
                    $imgForDetail = $largeUrl ?: ($origUrl ?: null);

                    $imageCount = count($p['images']);

                    $photoTitle = $p['photo_title'] ?? 'Photo';
                    $photographerTh = trim((string)($p['photographer_th'] ?? ''));
                    $photographerEn = trim((string)($p['photographer_en'] ?? ''));
                    $photographer = $photographerTh !== '' ? $photographerTh : $photographerEn;
                    ?>

                    <a class="photo-card" href="photo.php?id=<?= (int)$p['photo_nid'] ?>">
                        <div class="photo-thumb">
                            <?php if ($imgForGrid): ?>
                                <img
                                    src="<?= h($imgForGrid) ?>"
                                    alt="<?= h($photoTitle ?: 'Bird photo') ?>"
                                    loading="lazy" />
                                <?php if ($imageCount > 1): ?>
                                    <div class="multi-badge">+<?= $imageCount ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="thumb-placeholder">No Image</div>
                            <?php endif; ?>
                        </div>

                        <div class="photo-info">
                            <div class="photo-title"><?= h($photoTitle ?: 'Photo') ?></div>

                            <?php if ($photographer !== ''): ?>
                                <div class="photo-sub"><?= h($photographer) ?></div>
                            <?php else: ?>
                                <?php if ($imgForDetail): ?>
                                    <div class="photo-sub">Large available</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </a>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>