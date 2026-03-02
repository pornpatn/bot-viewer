<?php

function fetch_bird_detail(PDO $pdo, int $nid): ?array {
  $sql = "
  SELECT
    b.nid,
    b.title,

    /* TEXT */
    (SELECT GROUP_CONCAT(field_thai_name_value ORDER BY delta SEPARATOR ' / ')
     FROM dpl_field_data_field_thai_name
     WHERE entity_id=b.nid AND deleted=0) AS thai_names,

    (SELECT GROUP_CONCAT(field_english_name_value ORDER BY delta SEPARATOR ' / ')
     FROM dpl_field_data_field_english_name
     WHERE entity_id=b.nid AND deleted=0) AS english_names,

    (SELECT GROUP_CONCAT(field_species_value ORDER BY delta SEPARATOR ' / ')
     FROM dpl_field_data_field_species
     WHERE entity_id=b.nid AND deleted=0) AS species_names,

    (SELECT GROUP_CONCAT(field_race_value ORDER BY delta SEPARATOR ' / ')
     FROM dpl_field_data_field_race
     WHERE entity_id=b.nid AND deleted=0) AS race_values,

    (SELECT GROUP_CONCAT(field_size_value ORDER BY delta SEPARATOR ' / ')
     FROM dpl_field_data_field_size
     WHERE entity_id=b.nid AND deleted=0) AS size_values,

    (SELECT GROUP_CONCAT(field_habitat_value ORDER BY delta SEPARATOR '\\n\\n')
     FROM dpl_field_data_field_habitat
     WHERE entity_id=b.nid AND deleted=0) AS habitat_values,

    (SELECT GROUP_CONCAT(field_notes_value ORDER BY delta SEPARATOR '\\n\\n')
     FROM dpl_field_data_field_notes
     WHERE entity_id=b.nid AND deleted=0) AS notes_values,

    (SELECT GROUP_CONCAT(field_taxonomy_value ORDER BY delta SEPARATOR '\\n\\n')
     FROM dpl_field_data_field_taxonomy
     WHERE entity_id=b.nid AND deleted=0) AS taxonomy_text_values,

    /* NUMBER */
    (SELECT MAX(field_taxon_order_value)
     FROM dpl_field_data_field_taxon_order
     WHERE entity_id=b.nid AND deleted=0) AS taxon_order,

    /* TAXONOMY TERMS */
    (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY f.delta SEPARATOR ' / ')
     FROM dpl_field_data_field_sci_order f
     LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_sci_order_tid
     WHERE f.entity_id=b.nid AND f.deleted=0) AS sci_orders,

    (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY f.delta SEPARATOR ' / ')
     FROM dpl_field_data_field_sci_family f
     LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_sci_family_tid
     WHERE f.entity_id=b.nid AND f.deleted=0) AS sci_families,

    (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY f.delta SEPARATOR ' / ')
     FROM dpl_field_data_field_conservation_status f
     LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_conservation_status_tid
     WHERE f.entity_id=b.nid AND f.deleted=0) AS conservation_statuses,

    (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY f.delta SEPARATOR ' / ')
     FROM dpl_field_data_field_distribution f
     LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_distribution_tid
     WHERE f.entity_id=b.nid AND f.deleted=0) AS distributions,

    (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY f.delta SEPARATOR ' / ')
     FROM dpl_field_data_field_seasonal_status f
     LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_seasonal_status_tid
     WHERE f.entity_id=b.nid AND f.deleted=0) AS seasonal_statuses,

    (SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY f.delta SEPARATOR ' / ')
     FROM dpl_field_data_field_th_conversation_status f
     LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_th_conversation_status_tid
     WHERE f.entity_id=b.nid AND f.deleted=0) AS th_conversation_statuses

  FROM dpl_node b
  WHERE b.type='bird' AND b.nid=:nid
  LIMIT 1
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':nid' => $nid]);
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Pull photos for a bird: one row per image (delta 0..3).
function fetch_photos_for_bird(PDO $pdo, int $birdNid): array {
  $sql = "
  SELECT
    p.nid AS photo_nid,
    p.title AS photo_title,
    p.created AS photo_created,

    img.delta,
    fm.uri AS file_uri,

    pe.field_photographer_english_name_value AS photographer_en,
    pt.field_photographer_thai_name_value AS photographer_th

  FROM dpl_node p

  JOIN dpl_field_data_field_bird ref
    ON ref.entity_id=p.nid
    AND ref.entity_type='node'
    AND ref.bundle='photo'
    AND ref.deleted=0
    AND ref.field_bird_target_id=:bird_nid

  LEFT JOIN dpl_field_data_field_image img
    ON img.entity_id=p.nid
    AND img.entity_type='node'
    AND img.bundle='photo'
    AND img.deleted=0

  LEFT JOIN dpl_file_managed fm
    ON fm.fid = img.field_image_fid

  LEFT JOIN dpl_field_data_field_photographer_english_name pe
    ON pe.entity_id=p.nid AND pe.deleted=0
  LEFT JOIN dpl_field_data_field_photographer_thai_name pt
    ON pt.entity_id=p.nid AND pt.deleted=0

  WHERE p.type='photo'
  ORDER BY p.created DESC, p.nid DESC, img.delta ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':bird_nid' => $birdNid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Group rows into per-photo with images[]
  $byPhoto = [];
  foreach ($rows as $r) {
    $pid = (int)$r['photo_nid'];
    if (!isset($byPhoto[$pid])) {
      $byPhoto[$pid] = [
        'photo_nid' => $pid,
        'photo_title' => $r['photo_title'] ?? '',
        'photo_created' => (int)($r['photo_created'] ?? 0),
        'photographer_en' => $r['photographer_en'] ?? '',
        'photographer_th' => $r['photographer_th'] ?? '',
        'images' => [],
      ];
    }

    if (!empty($r['file_uri'])) {
      $byPhoto[$pid]['images'][] = [
        'delta' => (int)($r['delta'] ?? 0),
        'file_uri' => $r['file_uri'],
      ];
    }
  }

  return array_values($byPhoto);
}