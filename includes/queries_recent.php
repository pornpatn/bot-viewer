<?php

function fetch_recent_bird_photos(PDO $pdo, int $limit = 8): array
{
    $sql = "
        SELECT
            p.nid AS photo_nid,
            p.title AS photo_title,
            p.created,
            ref.field_bird_target_id AS bird_nid,

            (
                SELECT fm.uri
                FROM dpl_field_data_field_image fi
                LEFT JOIN dpl_file_managed fm
                    ON fm.fid = fi.field_image_fid
                WHERE fi.entity_id = p.nid
                  AND fi.entity_type = 'node'
                  AND fi.bundle = 'photo'
                  AND fi.deleted = 0
                ORDER BY fi.delta ASC
                LIMIT 1
            ) AS image_uri,

            (
                SELECT GROUP_CONCAT(field_english_name_value ORDER BY delta SEPARATOR ' / ')
                FROM dpl_field_data_field_english_name
                WHERE entity_id = ref.field_bird_target_id
                  AND deleted = 0
            ) AS bird_english_names,

            (
                SELECT GROUP_CONCAT(field_thai_name_value ORDER BY delta SEPARATOR ' / ')
                FROM dpl_field_data_field_thai_name
                WHERE entity_id = ref.field_bird_target_id
                  AND deleted = 0
            ) AS bird_thai_names,

            (
                SELECT GROUP_CONCAT(field_species_value ORDER BY delta SEPARATOR ' / ')
                FROM dpl_field_data_field_species
                WHERE entity_id = ref.field_bird_target_id
                  AND deleted = 0
            ) AS bird_species_names

        FROM dpl_node p
        LEFT JOIN dpl_field_data_field_bird ref
            ON ref.entity_id = p.nid
           AND ref.entity_type = 'node'
           AND ref.bundle = 'photo'
           AND ref.deleted = 0
           AND ref.delta = 0

        WHERE p.status = 1
          AND p.type = 'photo'

        ORDER BY p.created DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
