<?php
function bot_fetch_orders(PDO $pdo): array
{
  $c = bot_config();

  $sql = "
    SELECT
      ord_term.tid   AS order_tid,
      ord_term.name  AS order_name,

      COUNT(DISTINCT b.nid) AS species_count,
      COUNT(DISTINCT fam.{$c['FAMILY_TID_COL']}) AS family_count,

      MIN(CAST(tax.{$c['TAXON_ORDER_COL']} AS UNSIGNED)) AS taxon_order_sort
    FROM dpl_node b

    LEFT JOIN {$c['ORDER_FIELD_TABLE']} ord
      ON ord.entity_id = b.nid
      AND ord.entity_type='node'
      AND ord.bundle='bird'
      AND ord.deleted=0

    LEFT JOIN dpl_taxonomy_term_data ord_term
      ON ord_term.tid = ord.{$c['ORDER_TID_COL']}

    LEFT JOIN {$c['FAMILY_FIELD_TABLE']} fam
      ON fam.entity_id = b.nid
      AND fam.entity_type='node'
      AND fam.bundle='bird'
      AND fam.deleted=0

    LEFT JOIN {$c['TAXON_ORDER_TABLE']} tax
      ON tax.entity_id = b.nid
      AND tax.entity_type='node'
      AND tax.bundle='bird'
      AND tax.deleted=0

    WHERE b.type='bird' AND b.status=1
    GROUP BY ord_term.tid, ord_term.name
    ORDER BY taxon_order_sort ASC, ord_term.name ASC
  ";

  return $pdo->query($sql)->fetchAll();
}

function bot_fetch_order_preview(PDO $pdo, int $orderTid, int $limit = 4): array
{
  $c = bot_config();

  $sql = "
    SELECT
      b.nid,
      COALESCE(en.{$c['FIELD_EN_COL']}, b.title) AS english_name,
      COALESCE(th.{$c['FIELD_THAI_COL']}, '') AS thai_name,
      COALESCE(sn.{$c['FIELD_SCI_COL']}, '')  AS scientific_name,
      COALESCE(pc.{$c['FIELD_PHOTO_COL']}, 0) AS photo_count
    FROM dpl_node b
    JOIN {$c['ORDER_FIELD_TABLE']} ord
      ON ord.entity_id=b.nid
      AND ord.entity_type='node'
      AND ord.bundle='bird'
      AND ord.deleted=0
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
    ORDER BY b.title ASC
    LIMIT {$limit}
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':order_tid' => $orderTid]);
  return $stmt->fetchAll();
}
