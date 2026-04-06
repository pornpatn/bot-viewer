<?php

/**
 * Base SELECT (shared)
 */
function get_article_base_sql()
{
    return "
        SELECT
            n.nid,
            n.title,
            n.created,
            n.changed,
            n.status,
            n.type,

            b.body_value,
            b.body_summary,
            b.body_format,

            fi.field_image_fid,
            fi.field_image_alt,
            fi.field_image_title,
            img.uri AS image_uri,
            img.filename AS image_filename,

            fd.field_document_fid,
            fd.field_document_display,
            fd.field_document_description,
            doc.uri AS document_uri,
            doc.filename AS document_filename,

            (
                SELECT GROUP_CONCAT(ttd.name ORDER BY ttd.name SEPARATOR ' | ')
                FROM dpl_field_data_field_tags ft
                LEFT JOIN dpl_taxonomy_term_data ttd
                    ON ttd.tid = ft.field_tags_tid
                WHERE ft.entity_id = n.nid
                  AND ft.entity_type = 'node'
                  AND ft.deleted = 0
            ) AS tag_names,

            ua.alias
        FROM dpl_node n
        LEFT JOIN dpl_field_data_body b
            ON b.entity_id = n.nid
           AND b.entity_type = 'node'
           AND b.deleted = 0

        LEFT JOIN dpl_field_data_field_image fi
            ON fi.entity_id = n.nid
           AND fi.entity_type = 'node'
           AND fi.deleted = 0

        LEFT JOIN dpl_file_managed img
            ON img.fid = fi.field_image_fid

        LEFT JOIN dpl_field_data_field_document fd
            ON fd.entity_id = n.nid
           AND fd.entity_type = 'node'
           AND fd.deleted = 0

        LEFT JOIN dpl_file_managed doc
            ON doc.fid = fd.field_document_fid

        LEFT JOIN dpl_url_alias ua
            ON ua.source = CONCAT('node/', n.nid)

        WHERE n.status = 1
          AND n.type = 'article'
    ";
}

function fetch_article_by_nid(PDO $pdo, $nid)
{
    $sql = get_article_base_sql() . "
        AND n.nid = :nid
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nid' => $nid]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function fetch_article_by_alias(PDO $pdo, $alias)
{
    $sql = get_article_base_sql() . "
        AND ua.alias = :alias
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':alias' => $alias]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}