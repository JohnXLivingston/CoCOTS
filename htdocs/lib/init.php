<?php

define('_COCOTS_INITIALIZED', true);
define('COCOTS_ROOT_DIR', realpath(__DIR__ . '/..') . '/');
define('COCOTS_VENDOR_DIR', realpath(__DIR__ . '/../../vendor') . '/');

header("X-Frame-Options: deny");
header("Content-Security-Policy: "
  . "default-src 'self'; "
  . "base-uri 'self'; "
  . "block-all-mixed-content; "
  . "font-src 'self'; "
  . "frame-ancestors 'self'; "
  . "img-src 'self'; "
  . "object-src 'none'; "
  . "script-src 'self'; "
  . "script-src-attr 'none'; " // NB: not compatible with Firefox (for now).
  . "style-src 'self'; "
  . "upgrade-insecure-requests; ");
header("Cross-Origin-Embedder-Policy: require-corp");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");
header("X-Content-Type-Options: nosniff");

require_once(COCOTS_ROOT_DIR . '../config/config.php');
require_once(COCOTS_ROOT_DIR . 'lib/exceptions.php');
require_once(COCOTS_ROOT_DIR . 'lib/i18n.php');
require_once(COCOTS_ROOT_DIR . 'lib/accounts.php');
require_once(COCOTS_ROOT_DIR . 'lib/application.php');
