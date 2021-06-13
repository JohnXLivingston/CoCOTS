<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

class I18n {
  private $lang;

  public function __construct($lang) {
    $this->lang = $lang ?: 'en';
    // FIXME: if lang is not valid, fallback (or die?)
  }

  public function currentLang() {
    return $this->lang;
  }

  public function currentDir() {
    return 'ltr';
  }

  public function translate($label) {
    return htmlspecialchars($this->translateSafe($label));
  }

  public function translateSafe($label) {
    return $label;
  }
}
