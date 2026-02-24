<?php
require_once __DIR__ . "/config/app.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$photoNid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($photoNid <= 0) die("Invalid photo id.");

/**
 * Photo page data (Drupal 7)
 * - Photo is a node type: 'photo'
 * - Linked bird nid via: dpl_field_data_field_bird.field_bird_target_id
 * - Photo image via: dpl_field_data_field_image.field_image_fid -> dpl_file_managed.uri
 * - Bird order/family via term refs on bird node
 * - Photographer via field_photographer_*_value on photo node (string fields)
 */
$sql = "
SELECT
  p.nid AS photo_nid,
  p.title AS photo_title,
  p.created AS photo_created,

  fm.uri AS file_uri,

  pen.field_photographer_english_name_value AS photographer_en,
  pth.field_photographer_thai_name_value    AS photographer_th,

  b.nid AS bird_nid,
  b.title AS bird_title,

  ord_term.name AS sci_order,
  fam_term.name AS sci_family,

  en.field_english_name_value AS bird_english_name,
  th.field_thai_name_value    AS bird_thai_name

FROM dpl_node p

-- photo -> bird link
LEFT JOIN dpl_field_data_field_bird fb
  ON fb.entity_type='node'
 AND fb.entity_id=p.nid
 AND fb.bundle='photo'
 AND fb.deleted=0
 AND fb.delta=0

LEFT JOIN dpl_node b
  ON b.nid = fb.field_bird_target_id
 AND b.type='bird'

-- bird order/family term refs
LEFT JOIN dpl_field_data_field_sci_order ord
  ON ord.entity_type='node'
 AND ord.entity_id=b.nid
 AND ord.bundle='bird'
 AND ord.deleted=0
 AND ord.delta=0
LEFT JOIN dpl_taxonomy_term_data ord_term
  ON ord_term.tid = ord.field_sci_order_tid

LEFT JOIN dpl_field_data_field_sci_family fam
  ON fam.entity_type='node'
 AND fam.entity_id=b.nid
 AND fam.bundle='bird'
 AND fam.deleted=0
 AND fam.delta=0
LEFT JOIN dpl_taxonomy_term_data fam_term
  ON fam_term.tid = fam.field_sci_family_tid

-- bird display names (optional)
LEFT JOIN dpl_field_data_field_english_name en
  ON en.entity_type='node'
 AND en.entity_id=b.nid
 AND en.bundle='bird'
 AND en.deleted=0
 AND en.delta=0

LEFT JOIN dpl_field_data_field_thai_name th
  ON th.entity_type='node'
 AND th.entity_id=b.nid
 AND th.bundle='bird'
 AND th.deleted=0
 AND th.delta=0

-- photo image field
LEFT JOIN dpl_field_data_field_image img
  ON img.entity_type='node'
 AND img.entity_id=p.nid
 AND img.bundle='photo'
 AND img.deleted=0
 AND img.delta=0

LEFT JOIN dpl_file_managed fm
  ON fm.fid = img.field_image_fid

-- photographer fields on photo node
LEFT JOIN dpl_field_data_field_photographer_english_name pen
  ON pen.entity_type='node'
 AND pen.entity_id=p.nid
 AND pen.bundle='photo'
 AND pen.deleted=0
 AND pen.delta=0

LEFT JOIN dpl_field_data_field_photographer_thai_name pth
  ON pth.entity_type='node'
 AND pth.entity_id=p.nid
 AND pth.bundle='photo'
 AND pth.deleted=0
 AND pth.delta=0

WHERE p.nid = ?
  AND p.type='photo'
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$photoNid]);
$photo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$photo) die("Photo not found.");

$thumbUrl = drupal_style_url($photo['file_uri'] ?? null, 'thumbnail');
$largeUrl = drupal_style_url($photo['file_uri'] ?? null, 'large');
$origUrl  = drupal_original_url($photo['file_uri'] ?? null);

// prefer large for display
$displayUrl = $largeUrl ?: ($origUrl ?: $thumbUrl);

// Bird name display (Thai + English if exists, fallback to title)
$birdNameLine = trim(($photo['bird_thai_name'] ?? '') . ' ' . ($photo['bird_english_name'] ?? ''));
if ($birdNameLine === '') $birdNameLine = $photo['bird_title'] ?: 'Bird';

include "includes/header.php";
?>

<div class="container">

  <div class="page-topbar">
    <a class="back-link" href="species.php?id=<?= (int)$photo['bird_nid'] ?>">← Back to species</a>
  </div>

  <div class="photo-header-card">
    <h1 class="photo-page-title">
      <?= htmlspecialchars($birdNameLine) ?>
    </h1>

    <?php if (!empty($photo['photographer_en']) || !empty($photo['photographer_th'])): ?>
      <div class="photo-credit">
        Photographer:
        <?php if (!empty($photo['photographer_en'])): ?>
          <span><?= htmlspecialchars($photo['photographer_en']) ?></span>
        <?php endif; ?>
        <?php if (!empty($photo['photographer_th'])): ?>
          (<?= htmlspecialchars($photo['photographer_th']) ?>)
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="photo-image-card">
    <?php if ($displayUrl): ?>
      <a href="<?= htmlspecialchars($origUrl ?: $displayUrl) ?>" target="_blank" rel="noopener noreferrer">
        <img
          class="photo-main-image"
          src="<?= htmlspecialchars($displayUrl) ?>"
          alt="<?= htmlspecialchars($photo['photo_title'] ?: 'Bird photo') ?>"
          loading="lazy" />
      </a>
      <div class="photo-hint">Click image to open full size</div>
    <?php else: ?>
      <div class="empty-state">No image file found for this photo.</div>
    <?php endif; ?>
  </div>

  <div class="section">
    <div class="section-header">
      <h2>Photo Info</h2>
    </div>

    <div class="info-card">
      <div class="info-row">
        <div class="info-label">Species</div>
        <div class="info-value">
          <a class="inline-link" href="species.php?id=<?= (int)$photo['bird_nid'] ?>">
            <?= htmlspecialchars($birdNameLine) ?>
          </a>
        </div>
      </div>

      <div class="info-row">
        <div class="info-label">Order</div>
        <div class="info-value"><?= htmlspecialchars($photo['sci_order'] ?? '-') ?></div>
      </div>

      <div class="info-row">
        <div class="info-label">Family</div>
        <div class="info-value"><?= htmlspecialchars($photo['sci_family'] ?? '-') ?></div>
      </div>
    </div>
  </div>

  <!-- COMMENTS -->
  <?php render_comments_section($pdo, $photoNid); ?>

</div>

<?php include "includes/footer.php"; ?>