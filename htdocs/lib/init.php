<?php

define('_COCOTS_INITIALIZED', true);
define('COCOTS_ROOT_DIR', realpath(__DIR__ . '/..') . '/');
define('COCOTS_VENDOR_DIR', realpath(__DIR__ . '/../../vendor') . '/');

require_once(COCOTS_ROOT_DIR . '../config/config.php');

if (!defined('STDIN')) {
  // This is a web call.
  require(realpath(__DIR__ . '/../lib/headers.php'));
}

require_once(COCOTS_ROOT_DIR . 'lib/exceptions.php');
require_once(COCOTS_ROOT_DIR . 'lib/i18n.php');
require_once(COCOTS_ROOT_DIR . 'lib/accounts.php');
require_once(COCOTS_ROOT_DIR . 'lib/moderators.php');
require_once(COCOTS_ROOT_DIR . 'lib/application.php');
