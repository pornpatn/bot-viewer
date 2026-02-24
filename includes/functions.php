<?php

function drupal_uri_to_relative(?string $uri): ?string {
    if (!$uri) return null;

    if (strpos($uri, 'public://') === 0) {
        return substr($uri, strlen('public://'));
    }

    return null;
}

function drupal_original_url(?string $uri): ?string {
    $rel = drupal_uri_to_relative($uri);
    if (!$rel) return null;

    return get_media_base_url() . '/sites/default/files/' . $rel;
}

function drupal_style_url(?string $uri, string $style): ?string {
    $rel = drupal_uri_to_relative($uri);
    if (!$rel) return null;

    return get_media_base_url() . "/sites/default/files/styles/{$style}/public/" . $rel;
}

function get_media_base_url(): string {
    return MEDIA_BASE_URL;
}

function load_drupal_comments(PDO $pdo, int $nid): array {
  $sql = "
    SELECT
      c.cid,
      c.uid,
      c.name AS anon_name,
      u.name AS user_name,
      c.created,
      cb.comment_body_value AS body
    FROM dpl_comment c
    LEFT JOIN dpl_users u
      ON u.uid = c.uid
    LEFT JOIN dpl_field_data_comment_body cb
      ON cb.entity_type = 'comment'
     AND cb.entity_id   = c.cid
     AND cb.deleted     = 0
     AND cb.delta       = 0
    WHERE c.nid = ?
      AND c.status = 1
    ORDER BY c.created ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([$nid]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function comment_author_name(array $c): string {
  $user = trim((string)($c['user_name'] ?? ''));
  if ($user !== '') return $user;

  $anon = trim((string)($c['anon_name'] ?? ''));
  return $anon !== '' ? $anon : 'Anonymous';
}

/**
 * Renders a full "Comments" section (title + count + cards).
 * Safe for Phase 1: escapes comment body to prevent XSS.
 */
function render_comments_section(PDO $pdo, int $nid, string $title = 'Comments'): void {
  $comments = load_drupal_comments($pdo, $nid);
  $count = count($comments);
  ?>
  <div class="section">
    <div class="section-header">
      <h2><?= htmlspecialchars($title) ?></h2>
      <div class="section-subtitle">
        <?= $count ?> comment<?= $count === 1 ? '' : 's' ?>
      </div>
    </div>

    <?php if ($count === 0): ?>
      <div class="empty-state">No comments yet.</div>
    <?php else: ?>
      <div class="comment-list">
        <?php foreach ($comments as $c): ?>
          <div class="comment-card">
            <div class="comment-meta">
              <div class="comment-author"><?= htmlspecialchars(comment_author_name($c)) ?></div>
              <div class="comment-date"><?= date('M j, Y', (int)$c['created']) ?></div>
            </div>
            <div class="comment-body">
              <?= nl2br(htmlspecialchars((string)($c['body'] ?? ''))) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php
}