<?php
require_once __DIR__ . "/config/app.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($nid <= 0) die("Invalid species id.");

/**
 * Load bird (species) detail.
 * Node type: 'bird'
 * Taxonomy term refs:
 *  - field_sci_order_tid
 *  - field_sci_family_tid
 *  - field_conservation_status_tid
 */
$sqlSpecies = "
SELECT
  n.nid AS id,
  n.title,
  n.status AS published,

  en.field_english_name_value AS english_name,
  th.field_thai_name_value    AS thai_name,

  t_order.name  AS sci_order,
  t_family.name AS sci_family,
  t_cons.name   AS conservation_status,

  b.body_value AS body

FROM dpl_node n

LEFT JOIN dpl_field_data_field_english_name en
  ON en.entity_type='node'
 AND en.entity_id=n.nid
 AND en.deleted=0
 AND en.delta=0

LEFT JOIN dpl_field_data_field_thai_name th
  ON th.entity_type='node'
 AND th.entity_id=n.nid
 AND th.deleted=0
 AND th.delta=0

LEFT JOIN dpl_field_data_field_sci_order so
  ON so.entity_type='node'
 AND so.entity_id=n.nid
 AND so.deleted=0
 AND so.delta=0
LEFT JOIN dpl_taxonomy_term_data t_order
  ON t_order.tid = so.field_sci_order_tid

LEFT JOIN dpl_field_data_field_sci_family sf
  ON sf.entity_type='node'
 AND sf.entity_id=n.nid
 AND sf.deleted=0
 AND sf.delta=0
LEFT JOIN dpl_taxonomy_term_data t_family
  ON t_family.tid = sf.field_sci_family_tid

LEFT JOIN dpl_field_data_field_conservation_status cs
  ON cs.entity_type='node'
 AND cs.entity_id=n.nid
 And cs.deleted=0
 AND cs.delta=0
LEFT JOIN dpl_taxonomy_term_data t_cons
  ON t_cons.tid = cs.field_conservation_status_tid

LEFT JOIN dpl_field_data_body b
  ON b.entity_type='node'
 AND b.entity_id=n.nid
 AND b.deleted=0
 AND b.delta=0

WHERE n.nid = ?
    AND n.type = 'bird'
LIMIT 1
";

$stmt = $pdo->prepare($sqlSpecies);
$stmt->execute([$nid]);
$species = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$species) die("Species not found.");

/**
 * Load photos linked to this bird.
 * Photo node type: 'photo'
 * Link field: dpl_field_data_field_bird.field_bird_target_id (points to bird nid)
 * Image field: dpl_field_data_field_image.field_image_fid -> dpl_file_managed.uri
 */
$sqlPhotos = "
SELECT
  p.nid               AS photo_nid,
  p.title             AS photo_title,
  p.created           AS created_ts,
  fm.uri              AS file_uri
FROM dpl_node p

JOIN dpl_field_data_field_bird fb
  ON fb.entity_type='node'
 AND fb.entity_id=p.nid
 AND fb.deleted=0
 AND fb.delta=0
 AND fb.field_bird_target_id = ?

LEFT JOIN dpl_field_data_field_image img
  ON img.entity_type='node'
 AND img.entity_id=p.nid
 AND img.deleted=0
 AND img.delta=0

LEFT JOIN dpl_file_managed fm
  ON fm.fid = img.field_image_fid

WHERE p.type='photo'
  AND p.status=1
ORDER BY p.created DESC
LIMIT 60
";

$stmt2 = $pdo->prepare($sqlPhotos);
$stmt2->execute([$nid]);
$photos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
?>

<div class="container">
    <div class="page-topbar">
        <a class="back-link" href="index.php">← Back to Home</a>
    </div>

    <!-- TOP: Species Detail -->
    <div class="species-header">
        <div>
            <h1 class="species-title">
                <?= htmlspecialchars($species['title'] ?: 'Species') ?>
            </h1>

            <div class="species-subtitle">
                <?php if (!empty($species['english_name'])): ?>
                    <?= htmlspecialchars($species['english_name']) ?>
                <?php endif; ?>
                <?php if (!empty($species['thai_name'])): ?>
                    <?php if (!empty($species['english_name'])): ?> • <?php endif; ?>
                    <?= htmlspecialchars($species['thai_name']) ?>
                <?php endif; ?>
            </div>

            <div class="badges">
                <?php if (!empty($species['conservation_status'])): ?>
                    <span class="pill pill-muted"><?= htmlspecialchars($species['conservation_status']) ?></span>
                <?php endif; ?>
                <?php if ((int)$species['published'] === 1): ?>
                    <span class="pill pill-green">Published</span>
                <?php else: ?>
                    <span class="pill pill-gray">Unpublished</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="species-meta">
            <div class="meta-row">
                <div class="meta-label">Order</div>
                <div class="meta-value"><?= htmlspecialchars($species['sci_order'] ?? '-') ?></div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Family</div>
                <div class="meta-value"><?= htmlspecialchars($species['sci_family'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($species['body'])): ?>
        <div class="section">
            <h2>Profile</h2>
            <div class="content-card">
                <?= nl2br(htmlspecialchars($species['body'])) ?>
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
                <?= count($photos) ?> photo<?= count($photos) === 1 ? "" : "s" ?>
            </div>
        </div>

        <?php if (count($photos) === 0): ?>
            <div class="empty-state">No photos available yet.</div>
        <?php else: ?>
            <div class="photo-grid">
                <?php foreach ($photos as $p): ?>
                    <?php
                    $thumbUrl = drupal_style_url($p['file_uri'] ?? null, 'thumbnail');
                    $largeUrl = drupal_style_url($p['file_uri'] ?? null, 'large');
                    $origUrl  = drupal_original_url($p['file_uri'] ?? null);

                    // Fallbacks if derivative not generated or missing:
                    $imgForGrid = $thumbUrl ?: ($origUrl ?: null);
                    $imgForDetail = $largeUrl ?: ($origUrl ?: null);
                    ?>

                    <a class="photo-card" href="photo.php?id=<?= (int)$p['photo_nid'] ?>">
                        <div class="photo-thumb">
                            <?php if ($imgForGrid): ?>
                                <img
                                    src="<?= htmlspecialchars($imgForGrid) ?>"
                                    alt="<?= htmlspecialchars($p['photo_title'] ?: 'Bird photo') ?>"
                                    loading="lazy" />
                            <?php else: ?>
                                <div class="thumb-placeholder">No Image</div>
                            <?php endif; ?>
                        </div>

                        <div class="photo-info">
                            <div class="photo-title">
                                <?= htmlspecialchars($p['photo_title'] ?: 'Photo') ?>
                            </div>

                            <?php if ($imgForDetail): ?>
                                <div class="photo-sub">Large available</div>
                            <?php endif; ?>
                        </div>
                    </a>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include "includes/footer.php"; ?>