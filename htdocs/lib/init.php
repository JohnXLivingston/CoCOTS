<?php

define('_COCOTS_INITIALIZED', true);
define('COCOTS_ROOT_DIR', realpath(__DIR__ . '/..') . '/');

require(COCOTS_ROOT_DIR . '../config/config.php');
require(COCOTS_ROOT_DIR . 'lib/i18n.php');

class Application {
  public $loc;

  public function __construct() {
    $this->loc = new I18n(COCOTS_DEFAULT_LANGUAGE);
  }
}

$app = new Application();
