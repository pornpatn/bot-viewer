<?php
require_once __DIR__ . '/includes/bootstrap.php';

$nid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($nid <= 0) die("Invalid photo id.");

$photo = fetch_photo_detail($pdo, $nid);
if (!$photo) die("Photo not found.");

$imgDelta = isset($_GET['img']) ? (int)$_GET['img'] : 0;
$main = pick_photo_image($photo['images'] ?? [], $imgDelta);
$mainUri = $main['file_uri'] ?? null;

$mainLarge = $mainUri ? (drupal_style_url($mainUri, 'large') ?: drupal_original_url($mainUri)) : null;
$mainOrig  = $mainUri ? drupal_original_url($mainUri) : null;

$photographer = trim((string)$photo['photographer_th']) ?: trim((string)$photo['photographer_en']);

$backUrl  = !empty($photo['bird_nid']) ? ("species.php?id=" . (int)$photo['bird_nid']) : "index.php";
$backText = !empty($photo['bird_nid']) ? "← Back to Species" : "← Back to Home";

// Date formatting: full date without time
$formattedDate = '';
if (!empty($photo['photo_date'])) {
    try {
        $dt = new DateTime($photo['photo_date']);
        $formattedDate = $dt->format('F j, Y'); // November 5, 2011
    } catch (Exception $e) {
        // fallback: strip time if present
        $formattedDate = preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', (string)$photo['photo_date']);
    }
}

$bird = $photo['bird'] ?? null;
$birdThai = trim((string)($bird['thai_names'] ?? ''));
$birdEng  = trim((string)($bird['english_names'] ?? ''));
$birdSci  = trim((string)($bird['species_names'] ?? ''));
$birdOrder  = trim((string)($bird['order_names'] ?? ''));
$birdFamily = trim((string)($bird['family_names'] ?? ''));

include "includes/header.php";
?>

<div class="container">
  <div class="page-topbar">
    <a class="back-link" href="<?= h($backUrl) ?>"><?= h($backText) ?></a>
  </div>

  <div class="photo-header">
    <h1 class="photo-title"><?= h($photo['photo_title'] ?? 'Photo') ?></h1>
  </div>

  <div class="photo-layout">
    <!-- LEFT: Viewer -->
    <div class="photo-viewer">
      <div class="viewer-frame">
        <?php if ($mainLarge): ?>
          <a href="<?= h($mainOrig ?: $mainLarge) ?>" target="_blank" rel="noopener">
            <img class="viewer-img" src="<?= h($mainLarge) ?>" alt="<?= h($main['alt'] ?? 'Photo') ?>" />
          </a>
        <?php else: ?>
          <div class="empty-state">No image available.</div>
        <?php endif; ?>
      </div>

      <?php if (!empty($photo['images']) && count($photo['images']) > 1): ?>
        <div class="thumb-strip">
          <?php foreach ($photo['images'] as $img): ?>
            <?php
              $u = $img['file_uri'] ?? null;
              $thumb = $u ? (drupal_style_url($u, 'thumbnail') ?: drupal_original_url($u)) : null;
              $switchUrl = "photo.php?id=" . (int)$photo['photo_nid'] . "&img=" . (int)$img['delta'];
            ?>
            <a class="thumb <?= ((int)$img['delta'] === $imgDelta) ? 'is-active' : '' ?>" href="<?= h($switchUrl) ?>">
              <?php if ($thumb): ?>
                <img src="<?= h($thumb) ?>" alt="" loading="lazy" />
              <?php else: ?>
                <div class="thumb-placeholder">No</div>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Meta -->
    <div class="photo-meta">
      <div class="content-card">

        <!-- Bird block moved here -->
        <?php if (!empty($photo['bird_nid']) && $bird): ?>
          <div class="meta-bird">
            <div class="meta-bird-title">
              <a class="inline-link" href="species.php?id=<?= (int)$photo['bird_nid'] ?>">
                <?= h($birdEng !== '' ? $birdEng : ($birdThai !== '' ? $birdThai : ($bird['title'] ?? 'Bird'))) ?>
              </a>
            </div>

            <?php if ($birdThai !== '' || $birdEng !== ''): ?>
              <div class="meta-bird-names">
                <?php if ($birdThai !== ''): ?><span><?= h($birdThai) ?></span><?php endif; ?>
                <?php if ($birdThai !== '' && $birdEng !== ''): ?><span class="sep">•</span><?php endif; ?>
                <?php if ($birdEng !== ''): ?><span><?= h($birdEng) ?></span><?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($birdSci !== ''): ?>
              <div class="meta-bird-sci"><em><?= h($birdSci) ?></em></div>
            <?php endif; ?>

            <?php if ($birdOrder !== '' || $birdFamily !== ''): ?>
              <div class="meta-bird-tax">
                <?php if ($birdOrder !== ''): ?>
                  <div class="meta-row">
                    <div class="meta-label">Order</div>
                    <div class="meta-value"><?= h($birdOrder) ?></div>
                  </div>
                <?php endif; ?>
                <?php if ($birdFamily !== ''): ?>
                  <div class="meta-row">
                    <div class="meta-label">Family</div>
                    <div class="meta-value"><?= h($birdFamily) ?></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="meta-divider"></div>
        <?php endif; ?>

        <?php if ($photographer !== ''): ?>
          <div class="meta-row">
            <div class="meta-label">Photographer</div>
            <div class="meta-value"><?= h($photographer) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($formattedDate !== ''): ?>
          <div class="meta-row">
            <div class="meta-label">Date</div>
            <div class="meta-value"><?= h($formattedDate) ?></div>
          </div>
        <?php endif; ?>

        <?php if (trim((string)$photo['location_text']) !== ''): ?>
          <div class="meta-row">
            <div class="meta-label">Location</div>
            <div class="meta-value"><?= nl2br(h($photo['location_text'])) ?></div>
          </div>
        <?php endif; ?>

        <?php if (trim((string)$photo['plumage_name']) !== ''): ?>
          <div class="meta-row">
            <div class="meta-label">Plumage</div>
            <div class="meta-value"><?= h($photo['plumage_name']) ?></div>
          </div>
        <?php endif; ?>

        <?php if (trim((string)$photo['race_value']) !== ''): ?>
          <div class="meta-row">
            <div class="meta-label">Race</div>
            <div class="meta-value"><?= h($photo['race_value']) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($mainOrig): ?>
          <div class="meta-row">
            <div class="meta-label">File</div>
            <div class="meta-value">
              <a class="inline-link" href="<?= h($mainOrig) ?>" target="_blank" rel="noopener">Open original</a>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <?php if (trim((string)$photo['body']) !== ''): ?>
    <div class="section">
      <h2>Description</h2>
      <div class="content-card">
        <?= nl2br(h($photo['body'])) ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (trim((string)$photo['taxonomy_text']) !== ''): ?>
    <div class="section">
      <h2>Notes</h2>
      <div class="content-card">
        <?= nl2br(h($photo['taxonomy_text'])) ?>
      </div>
    </div>
  <?php endif; ?>

  <?php render_comments_section($pdo, $nid); ?>
</div>

<?php include "includes/footer.php"; ?>