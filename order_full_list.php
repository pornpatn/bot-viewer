<?php
require_once __DIR__ . '/includes/bootstrap.php';

$orderTid = isset($_GET['order_tid']) ? (int)$_GET['order_tid'] : 0;
if ($orderTid <= 0) {
  json_out(['family_count' => 0, 'species_count' => 0, 'families' => []], 400);
}

json_out(bot_fetch_order_full($pdo, $orderTid));