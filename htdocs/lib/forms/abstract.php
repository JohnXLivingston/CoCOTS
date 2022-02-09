<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/fields.php');

abstract class Form {
  protected $app;
  protected $fields = array();
  protected $error_messages = array();

  public function __construct($app) {
    $this->app = $app;
    $this->initFields();
  }

  abstract protected function initFields();

  abstract public function getFormName();

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

  public function check() {
    $is_valid = true;
    foreach ($this->fields as $field) {
      if (!$field->check()) {
        $is_valid = false;
      }
      $error_codes = $field->getErrorCodes();
      foreach ($error_codes as $error_code) {
        $error_label = $this->app->loc->translate($error_code);
        array_push($this->error_messages, $field->getLabel() . ': ' . $error_label);
      }
    }
    return $is_valid;
  }

  public function getErrorMessages() {
    return $this->error_messages;
  }

  public function getErrorMessagesHtml() {
    $messages = $this->getErrorMessages();
    $htmls = array();
    foreach ($messages as $message) {
      array_push($htmls, htmlspecialchars($message));
    }
    return $htmls;
  }

  abstract public function save();
}
