<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/fields.php');

abstract class Form {
  protected $app;
  protected $fields = array();

  public function __construct($app) {
    $this->app = $app;
    $this->initFields();
  }

  abstract protected function initFields();

  public function hasField($name) {
    return isset($this->fields[$name]);
  }
  
  public function getField($name) {
    return $this->fields[$name];
  }

  public function readPost() {
    foreach ($this->fields as $field) {
      $field->readPost();
    }
  }
}
