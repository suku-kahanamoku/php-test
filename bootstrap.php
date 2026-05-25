<?php

declare(strict_types=1);

define('ROOT',       __DIR__);
define('DATA_DIR',   ROOT . '/data');
define('SRC_DIR',    ROOT . '/src');

// Auth tokens (simulace – v produkci pres DB / JWT)
define('TOKEN_USER',  'test-token-abc123');
define('TOKEN_ADMIN', 'admin-token-xyz456');

require_once SRC_DIR . '/Request.php';
require_once SRC_DIR . '/Response.php';
require_once SRC_DIR . '/Router.php';
require_once SRC_DIR . '/Filter.php';
