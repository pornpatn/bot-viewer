<?php

function fetch_articles(PDO $pdo, $limit = 20)
{
    $sql = "
        SELECT
            n.nid,
            n.title,
            n.created,
            b.body_summary,
            ua.alias,

            (
                SELECT fm.uri
                FROM dpl_field_data_field_image fi
                LEFT JOIN dpl_file_managed fm
                    ON fm.fid = fi.field_image_fid
                WHERE fi.entity_id = n.nid
                  AND fi.entity_type = 'node'
                  AND fi.deleted = 0
                ORDER BY fi.delta ASC
                LIMIT 1
            ) AS image_uri

        FROM dpl_node n

        LEFT JOIN dpl_field_data_body b
            ON b.entity_id = n.nid
           AND b.entity_type = 'node'
           AND b.deleted = 0

        LEFT JOIN dpl_url_alias ua
            ON ua.source = CONCAT('node/', n.nid)

        WHERE n.status = 1
          AND n.type = 'article'

        ORDER BY n.created DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}