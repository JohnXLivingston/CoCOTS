<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

class I18n {
  private $fallbackLang = 'en';
  private $lang;
  private $translations = array(); // array of translations $key => $value

  public function __construct($lang) {
    $this->lang = $lang && $this->valideLang($lang) ? $lang : $this->fallbackLang;
    $this->load($this->fallbackLang);
    if ($this->fallbackLang !== $this->lang) {
      $this->load($this->lang);
    }
  }

  private function langFilePath($lang) {
    return realpath(COCOTS_ROOT_DIR . 'translations/' . $lang . '.lang');
  }
  
  private function valideLang($lang) {
    if (1 !== preg_match('/^[a-z]{2}(_[a-z]{2})?$/', $lang)) {
      return false;
    }
    // There must be a lang file.
    if(!is_file($this->langFilePath($lang))) {
      return false;
    }
    return true;
  }

  private function load($lang) {
    if (!$this->valideLang($lang)) {
      return;
    }
    if ($fp = @fopen($this->langFilePath($lang), 'r')) {
      while ($line = fscanf($fp, "%[^= ]%*[ =]%[^\n\r]")) {
        if (isset($line[1])) {
          list($key, $value) = $line;
          if ($key[0] === '#') {
            continue;
          }
          $this->translations[$key] = $value;
        }
      }
    }
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
    return $this->translations[$label] ?: $label;
  }
}
