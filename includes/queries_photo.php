<?php

function fetch_bird_brief(PDO $pdo, int $birdNid): ?array
{
    $sql = "
    SELECT
      b.nid,
      b.title,

      (SELECT GROUP_CONCAT(DISTINCT field_thai_name_value SEPARATOR ' / ')
       FROM dpl_field_data_field_thai_name
       WHERE entity_id=b.nid AND deleted=0) AS thai_names,

      (SELECT GROUP_CONCAT(DISTINCT field_english_name_value SEPARATOR ' / ')
       FROM dpl_field_data_field_english_name
       WHERE entity_id=b.nid AND deleted=0) AS english_names,

      (SELECT GROUP_CONCAT(DISTINCT field_species_value SEPARATOR ' / ')
       FROM dpl_field_data_field_species
       WHERE entity_id=b.nid AND deleted=0) AS species_names,

      (SELECT GROUP_CONCAT(DISTINCT t.name SEPARATOR ' / ')
       FROM dpl_field_data_field_sci_order f
       LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_sci_order_tid
       WHERE f.entity_id=b.nid AND f.deleted=0) AS order_names,

      (SELECT GROUP_CONCAT(DISTINCT t.name SEPARATOR ' / ')
       FROM dpl_field_data_field_sci_family f
       LEFT JOIN dpl_taxonomy_term_data t ON t.tid=f.field_sci_family_tid
       WHERE f.entity_id=b.nid AND f.deleted=0) AS family_names

    FROM dpl_node b
    WHERE b.type='bird' AND b.nid=:nid
    LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nid' => $birdNid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return null;

    return [
        'nid' => (int)$row['nid'],
        'title' => $row['title'] ?? '',
        'thai_names' => $row['thai_names'] ?? '',
        'english_names' => $row['english_names'] ?? '',
        'species_names' => $row['species_names'] ?? '',
        'order_names' => $row['order_names'] ?? '',
        'family_names' => $row['family_names'] ?? '',
    ];
}

function fetch_photo_detail(PDO $pdo, int $photoNid): ?array
{
    $sql = "
    SELECT
      p.nid AS photo_nid,
      p.title AS photo_title,
      p.created AS created_ts,

      ref.field_bird_target_id AS bird_nid,

      d.field_date_value AS photo_date,
      loc.field_location_value AS location_text,

      pe.field_photographer_english_name_value AS photographer_en,
      pt.field_photographer_thai_name_value    AS photographer_th,

      pr.field_race_value AS race_value,

      plum_term.name AS plumage_name,

      tax.field_taxonomy_value AS taxonomy_text,
      body.body_value AS body,

      img.delta,
      fm.uri AS file_uri,
      img.field_image_alt AS image_alt,
      img.field_image_title AS image_title

    FROM dpl_node p

    LEFT JOIN dpl_field_data_field_bird ref
      ON ref.entity_type='node'
     AND ref.entity_id=p.nid
     AND ref.bundle='photo'
     AND ref.deleted=0

    LEFT JOIN dpl_field_data_field_date d
      ON d.entity_type='node'
     AND d.entity_id=p.nid
     AND d.bundle='photo'
     AND d.deleted=0
     AND d.delta=0

    LEFT JOIN dpl_field_data_field_location loc
      ON loc.entity_type='node'
     AND loc.entity_id=p.nid
     AND loc.bundle='photo'
     AND loc.deleted=0
     AND loc.delta=0

    LEFT JOIN dpl_field_data_field_photographer_english_name pe
      ON pe.entity_type='node'
     AND pe.entity_id=p.nid
     AND pe.bundle='photo'
     AND pe.deleted=0
     AND pe.delta=0

    LEFT JOIN dpl_field_data_field_photographer_thai_name pt
      ON pt.entity_type='node'
     AND pt.entity_id=p.nid
     AND pt.bundle='photo'
     AND pt.deleted=0
     AND pt.delta=0

    LEFT JOIN dpl_field_data_field_race pr
      ON pr.entity_type='node'
     AND pr.entity_id=p.nid
     AND pr.bundle='photo'
     AND pr.deleted=0
     AND pr.delta=0

    LEFT JOIN dpl_field_data_field_plumage plum
      ON plum.entity_type='node'
     AND plum.entity_id=p.nid
     AND plum.bundle='photo'
     AND plum.deleted=0
     AND plum.delta=0

    LEFT JOIN dpl_taxonomy_term_data plum_term
      ON plum_term.tid = plum.field_plumage_tid

    LEFT JOIN dpl_field_data_field_taxonomy tax
      ON tax.entity_type='node'
     AND tax.entity_id=p.nid
     AND tax.bundle='photo'
     AND tax.deleted=0
     AND tax.delta=0

    LEFT JOIN dpl_field_data_body body
      ON body.entity_type='node'
     AND body.entity_id=p.nid
     AND body.deleted=0
     AND body.delta=0

    LEFT JOIN dpl_field_data_field_image img
      ON img.entity_type='node'
     AND img.entity_id=p.nid
     AND img.bundle='photo'
     AND img.deleted=0

    LEFT JOIN dpl_file_managed fm
      ON fm.fid = img.field_image_fid

    WHERE p.nid = :nid
      AND p.type = 'photo'
    ORDER BY img.delta ASC
    LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nid' => $photoNid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) return null;

    $base = $rows[0];

    $photo = [
        'photo_nid' => (int)$base['photo_nid'],
        'photo_title' => $base['photo_title'] ?? 'Photo',
        'created_ts' => (int)($base['created_ts'] ?? 0),

        'bird_nid' => isset($base['bird_nid']) ? (int)$base['bird_nid'] : 0,
        'bird' => null, // filled below

        'photo_date' => $base['photo_date'] ?? '',
        'location_text' => $base['location_text'] ?? '',

        'photographer_en' => $base['photographer_en'] ?? '',
        'photographer_th' => $base['photographer_th'] ?? '',

        'race_value' => $base['race_value'] ?? '',
        'plumage_name' => $base['plumage_name'] ?? '',

        'taxonomy_text' => $base['taxonomy_text'] ?? '',
        'body' => $base['body'] ?? '',

        'images' => [],
    ];

    foreach ($rows as $r) {
        if (!empty($r['file_uri'])) {
            $photo['images'][] = [
                'delta' => (int)($r['delta'] ?? 0),
                'file_uri' => $r['file_uri'],
                'alt' => $r['image_alt'] ?? '',
                'title' => $r['image_title'] ?? '',
            ];
        }
    }

    usort($photo['images'], fn($a, $b) => ($a['delta'] <=> $b['delta']));

    // Attach bird brief
    if (!empty($photo['bird_nid'])) {
        $photo['bird'] = fetch_bird_brief($pdo, (int)$photo['bird_nid']);
    }

    return $photo;
}

function pick_photo_image(array $images, int $deltaWanted = 0): ?array
{
    foreach ($images as $img) {
        if ((int)($img['delta'] ?? 0) === $deltaWanted) return $img;
    }
    return $images[0] ?? null;
}
