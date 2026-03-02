<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/bot_config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// bird list
require_once __DIR__ . '/queries_orders.php';
require_once __DIR__ . '/queries_full.php';

// bird species
require_once __DIR__ . '/queries_species.php';

// photo
require_once __DIR__ . '/queries_photo.php';

bot_pdo_init($pdo);