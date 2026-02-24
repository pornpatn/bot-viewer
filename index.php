<?php
require_once __DIR__ . "/config/app.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/functions.php";

$sql = "
SELECT
  b.nid AS bird_nid,
  b.title AS bird_title,

  ord_term.name AS sci_order,
  fam_term.name AS sci_family,

  tax.field_taxon_order_value AS taxon_order,

  COALESCE(pc.field_photo_count_count, 0) AS photo_count

FROM dpl_node b

LEFT JOIN dpl_field_data_field_sci_order ord
  ON ord.entity_id = b.nid
  AND ord.entity_type='node'
  AND ord.bundle='bird'
  AND ord.deleted=0

LEFT JOIN dpl_taxonomy_term_data ord_term
  ON ord_term.tid = ord.field_sci_order_tid

LEFT JOIN dpl_field_data_field_sci_family fam
  ON fam.entity_id = b.nid
  AND fam.entity_type='node'
  AND fam.bundle='bird'
  AND fam.deleted=0

LEFT JOIN dpl_taxonomy_term_data fam_term
  ON fam_term.tid = fam.field_sci_family_tid

LEFT JOIN dpl_field_data_field_taxon_order tax
  ON tax.entity_id = b.nid
  AND tax.entity_type='node'
  AND tax.bundle='bird'
  AND tax.deleted=0

LEFT JOIN dpl_field_data_field_photo_count pc
  ON pc.entity_id = b.nid
  AND pc.entity_type='node'
  AND pc.bundle='bird'
  AND pc.deleted=0

WHERE b.type='bird'
  AND b.status=1

ORDER BY
  CAST(tax.field_taxon_order_value AS UNSIGNED),
  ord_term.name,
  fam_term.name,
  b.title;
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Group as:
 * $data[order][family] = list of birds
 */
$data = [];
foreach ($rows as $r) {
    $order  = $r['sci_order']  ?: 'Unknown Order';
    $family = $r['sci_family'] ?: 'Unknown Family';
    $data[$order][$family][] = $r;
}

include "includes/header.php";
?>

<div class="container">

    <?php foreach ($data as $orderName => $families): ?>
        <?php
        $familyCount = count($families);
        $speciesCount = 0;
        foreach ($families as $birds) $speciesCount += count($birds);
        ?>

        <section class="order-section">
            <div class="order-header">
                <h2 class="order-title"><?= htmlspecialchars($orderName) ?></h2>
                <div class="order-stats">
                    <?= $familyCount ?> <?= $familyCount === 1 ? 'family' : 'families' ?>
                    &nbsp;•&nbsp;
                    <?= $speciesCount ?> <?= $speciesCount === 1 ? 'species' : 'species' ?>
                </div>
            </div>

            <?php foreach ($families as $familyName => $birds): ?>
                <div class="family-card">

                    <button class="family-header" type="button">
                        <div class="family-name">
                            <?= htmlspecialchars($familyName) ?>
                        </div>

                        <div class="family-right">
                            <span class="family-stats">
                                <?= count($birds) ?> <?= count($birds) === 1 ? 'species' : 'species' ?>
                            </span>
                            <span class="family-toggle">▾</span>
                        </div>
                    </button>

                    <div class="species-list">
                        <?php foreach ($birds as $b): ?>
                            <?php $photoCount = (int)$b['photo_count']; ?>
                            <a class="species-row" href="species.php?id=<?= (int)$b['bird_nid'] ?>">
                                <div class="species-text">
                                    <div class="species-primary">
                                        <?= htmlspecialchars($b['bird_title']) ?>
                                    </div>
                                </div>

                                <div class="species-right">
                                    <span class="badge badge-photos">
                                        <?= $photoCount ?> photo<?= $photoCount === 1 ? '' : 's' ?>
                                    </span>
                                    <span class="chevron">›</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

</div>

<?php include "includes/footer.php"; ?>