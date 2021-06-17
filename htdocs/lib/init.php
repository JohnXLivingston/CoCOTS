<?php

define('_COCOTS_INITIALIZED', true);
define('COCOTS_ROOT_DIR', realpath(__DIR__ . '/..') . '/');

require_once(COCOTS_ROOT_DIR . '../config/config.php');
require_once(COCOTS_ROOT_DIR . 'lib/exceptions.php');
require_once(COCOTS_ROOT_DIR . 'lib/i18n.php');
require_once(COCOTS_ROOT_DIR . 'lib/accounts.php');
require_once(COCOTS_ROOT_DIR . 'lib/application.php');
