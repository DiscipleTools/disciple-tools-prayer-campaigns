<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/interfaces/dt-porch-loader-interface.php';
require_once __DIR__ . '/interfaces/dt-admin-menu-interface.php';

require_once __DIR__ . '/generic/dt-generic-porch-loader.php';
require_once __DIR__ . '/roles-and-permissions.php';
require_once __DIR__ . '/prayer-fuel-post-importer.php';

require_once __DIR__ . '/prayer-fuel-day-list.php';
require_once __DIR__ . '/prayer-fuel-admin-page.php';

