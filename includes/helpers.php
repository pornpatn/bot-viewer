<?php
function h(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_out(array $payload, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function render_safe_note(?string $html): string {
    if (!$html) return '';

    // Allow only iframe + very basic formatting
    $allowed = '<iframe><br><p><strong><em><ul><ol><li>';

    $clean = strip_tags($html, $allowed);

    // Extra safety: allow only YouTube embeds
    $clean = preg_replace_callback(
        '#<iframe[^>]+src="([^"]+)"[^>]*></iframe>#i',
        function ($matches) {
            $src = $matches[1];

            // Only allow youtube embed URLs
            if (preg_match('#^https://www\.youtube\.com/embed/#', $src)) {
                return '<div class="video-embed"><iframe src="' .
                    htmlspecialchars($src, ENT_QUOTES) .
                    '" frameborder="0" allowfullscreen loading="lazy"></iframe></div>';
            }
            return ''; // strip if not valid youtube embed
        },
        $clean
    );

    return $clean;
}