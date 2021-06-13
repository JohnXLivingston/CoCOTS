<?php

define('_COCOTS_INITIALIZED', true);
define('COCOTS_ROOT_DIR', realpath(__DIR__ . '/..') . '/');

require(COCOTS_ROOT_DIR . '../config/config.php');
require(COCOTS_ROOT_DIR . 'lib/i18n.php');

class Application {
  public $loc;
  public $presets;

  public function __construct() {
    $this->loc = new I18n(COCOTS_DEFAULT_LANGUAGE);
    $this->loadPresets();
  }

  protected function loadPresets() {
    $path = COCOTS_PRESETS_PATH;
    if (!$path) {
      throw new Exception('Missing presset in config.');
    }
    require_once(COCOTS_ROOT_DIR . 'lib/presets/abstract.php');
    if (strpos($path, '/') !== false) {
      require_once(realpath($path));
    } else {
      require_once(COCOTS_ROOT_DIR . 'lib/presets/' . $path);
    }
    $classname = COCOTS_PRESETS_CLASS;
    $this->presets = new $classname($this);
  }

  public function getForm($form) {
    if ($form === 'creation') {
      require(COCOTS_ROOT_DIR . 'lib/forms/creation.php');
      return new CreationForm($this);
    }
    throw new Exception('Invalid form name');
  }
}

$app = new Application();
