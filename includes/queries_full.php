<?php
function bot_fetch_order_full(PDO $pdo, int $orderTid): array {
  $c = bot_config();

  $sql = "
    SELECT
      b.nid,
      COALESCE(en.{$c['FIELD_EN_COL']}, b.title) AS english_name,
      COALESCE(th.{$c['FIELD_THAI_COL']}, '') AS thai_name,
      COALESCE(sn.{$c['FIELD_SCI_COL']}, '')  AS scientific_name,
      COALESCE(pc.{$c['FIELD_PHOTO_COL']}, 0) AS photo_count,
      fam_term.tid  AS family_tid,
      fam_term.name AS family_name
    FROM dpl_node b
    JOIN {$c['ORDER_FIELD_TABLE']} ord
      ON ord.entity_id=b.nid
      AND ord.entity_type='node'
      AND ord.bundle='bird'
      AND ord.deleted=0
    LEFT JOIN {$c['FAMILY_FIELD_TABLE']} fam
      ON fam.entity_id=b.nid
      AND fam.entity_type='node'
      AND fam.bundle='bird'
      AND fam.deleted=0
    LEFT JOIN dpl_taxonomy_term_data fam_term
      ON fam_term.tid = fam.{$c['FAMILY_TID_COL']}
    LEFT JOIN {$c['FIELD_EN_TABLE']} en
      ON en.entity_id=b.nid
      AND en.entity_type='node'
      AND en.bundle='bird'
      AND en.deleted=0
    LEFT JOIN {$c['FIELD_THAI_TABLE']} th
      ON th.entity_id=b.nid
      AND th.entity_type='node'
      AND th.bundle='bird'
      AND th.deleted=0
    LEFT JOIN {$c['FIELD_SCI_TABLE']} sn
      ON sn.entity_id=b.nid
      AND sn.entity_type='node'
      AND sn.bundle='bird'
      AND sn.deleted=0
    LEFT JOIN {$c['FIELD_PHOTO_TABLE']} pc
      ON pc.entity_id=b.nid
      AND pc.entity_type='node'
      AND pc.bundle='bird'
      AND pc.deleted=0
    WHERE b.type='bird' AND b.status=1
      AND ord.{$c['ORDER_TID_COL']} = :order_tid
    ORDER BY fam_term.name ASC, b.title ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':order_tid' => $orderTid]);
  $rows = $stmt->fetchAll();

  $families = [];
  $speciesCount = 0;

  foreach ($rows as $r) {
    $speciesCount++;
    $famTid  = isset($r['family_tid']) ? (int)$r['family_tid'] : 0;
    $famName = $r['family_name'] ?? 'Unknown family';

    if (!isset($families[$famTid])) {
      $families[$famTid] = [
        'family_tid' => $famTid,
        'family_name' => $famName,
        'species' => [],
      ];
    }

    $families[$famTid]['species'][] = [
      'nid' => (int)$r['nid'],
      'thai_name' => (string)$r['thai_name'],
      'english_name' => (string)$r['english_name'],
      'scientific_name' => (string)$r['scientific_name'],
      'photo_count' => (int)$r['photo_count'],
    ];
  }

  return [
    'family_count'  => count($families),
    'species_count' => $speciesCount,
    'families'      => array_values($families),
  ];
}