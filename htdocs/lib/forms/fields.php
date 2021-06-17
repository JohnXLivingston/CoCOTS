<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

abstract class Field {
  protected $name;
  protected $label;
  protected $value;
  protected $error_codes = array();
  protected $required = false;

  public function __construct($name, $options) {
    $this->name = $name;
    $this->value = null;
    if ($options['required'] === true) {
      $this->required = true;
    }
    if (isset($options['label'])) {
      $this->label = $options['label'];
    }
  }

  public function getValue() {
    return $this->value;
  }

  public function setValue($value) {
    $this->value = $value;
    return $this;
  }

  public function getLabel() {
    return $this->label;
  }

  public function getLabelHtml() {
    $html = '<label for="' . htmlspecialchars($this->name) . '">';
    $html.= htmlspecialchars($this->getLabel());
    $html.= '</label>';
    return $html;
  }

  public function readPost() {
    $this->setValue($_POST[$this->name]);
  }

  public function isRequired() {
    return $this->required;
  }

  public function getAttributes() {
    $attrs = array();
    $attrs['id'] = $this->name;
    if ($this->isRequired()) {
      $attrs['required'] = 'required';
    }
    if (count($this->error_codes) > 0) {
      $attrs['class'] = 'error';
    }
    return $attrs;
  }

  public function attributesHtml() {
    $attrs = $this->getAttributes();
    $html = '';
    foreach ($attrs as $key => $value) {
      $html.= ' ' . $key . '="' . htmlspecialchars($value) . '" ';
    }
    return $html;
  }

  abstract public function html();

  public function check() {
    $value = $this->getValue();
    if ($this->isRequired()) {
      if (!isset($value) || strval($value) === '') {
        $this->addErrorCode('error_field_required');
        return false;
      }
    }
    return true;
  }

  public function getErrorCodes() {
    return $this->error_codes;
  }

  public function hasErrorCode($error_code) {
    return in_array($error_code, $this->error_codes, true);
  }

  public function addErrorCode($error_code) {
    array_push($this->error_codes, $error_code);
  }
}

abstract class InputField extends Field {
  protected $placeholder = null;
  protected $title = null;
  protected $aria_label = null;

  public function __construct($name, $options) {
    parent::__construct($name, $options);
    if (isset($options['placeholder'])) {
      if ($options['placeholder'] === true) {
        // By default, we take the label as placeholder.
        $this->placeholder = $this->label;
      } else {
        $this->placeholder = $options['placeholder'];
      }
    }
    foreach (array('aria-label', 'title') as $f) {
      if (isset($options[$f])) {
        $this->$f = $options[$f];
      }
    }
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['name'] = $this->name;
    foreach (array('placeholder', 'aria-label', 'title') as $f) {
      if (isset($this->$f)) {
        $attrs[$f] = $this->$f;
      }
    }
    return $attrs;
  }

  function html() {
    return '<input ' . $this->attributesHtml() . '>';
  }
}

class TextField extends InputField {
  protected $pattern = null;
  public function __construct($name, $options) {
    parent::__construct($name, $options);
    if (isset($options['pattern'])) {
      $this->pattern = $options['pattern'];
    }
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['type'] = 'text';
    $attrs['value'] = $this->getValue();
    if (isset($this->pattern)) {
      $attrs['pattern'] = $this->pattern;
    }
    return $attrs;
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    if ($this->pattern) {
      $value = $this->getValue();
      $string = isset($value) ? strval($value) : '';
      if (!preg_match('/^' . $this->pattern . '$/', $string)) {
        $this->addErrorCode('error_field_pattern');
        return false;
      }
    }

    return true;
  }
}

class EmailField extends TextField {
  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['type'] = 'email';
    return $attrs;
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    $value = $this->getValue();
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
      $this->addErrorCode('error_field_email_invalid');
      return false;
    }

    return true;
  }
}

class SelectField extends Field {
  protected $options = array();

  public function __construct($name, $options) {
    parent::__construct($name, $options);
    if(is_array($options['options'])) {
      $this->options = $options['options'];
    }
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['name'] = $this->name;
    return $attrs;
  }

  function html() {
    $html = '<select ' . $this->attributesHtml() . '>';
    $html.= '<option value=""';
    if (!isset($this->value)) {
      $html.= ' selected="selected" ';
    }
    $html.= '></option>';
    foreach ($this->options as $idx => $option) {
      $html.= '<option value="' . htmlspecialchars($option['value']) . '" ';
      if (isset($this->value) && $this->value === $option['value']) {
        $html.= ' selected="selected" ';
      }
      $option_label = isset($option['label']) ? $option['label'] : $option['value'];
      $html.= '>' . htmlspecialchars($option_label) . '</option>';
    }
    $html.= '</select>';
    return $html;
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    $value = $this->getValue();
    $value_found = false;
    foreach ($this->options as $option) {
      if ($option['value'] === $value) {
        $value_found = true;
      }
    }
    if (!$value_found) {
      $this->addErrorCode('error_field_select_invalid_value');
      return false;
    }

    return true;
  }
}

class CheckboxField extends Field {
  protected $disabled = false;
  protected $default = false;

  public function __construct($name, $options) {
    parent::__construct($name, $options);
    $this->value = false;
    if(isset($options['disabled'])) {
      $this->disabled = boolval($options['disabled']);
    }
    if(isset($options['default'])) {
      $this->default = boolval($options['default']);
    }
  }

  public function getValue() {
    if ($this->disabled) {
      return $this->default;
    }
    return $this->value;
  }

  public function setValue($value) {
    if ($this->disabled) { return $this; }
    $this->value = boolval($value);
    return $this;
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['name'] = $this->name;
    $attrs['type'] = 'checkbox';
    $attrs['value'] = '1';
    if ($this->getValue()) {
      $attrs['checked'] = 'checked';
    }
    if ($this->disabled) {
      $attrs['disabled'] = 'disabled';
    }
    return $attrs;
  }

  function html() {
    return '<input ' . $this->attributesHtml() . '>';
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    if ($this->isRequired() && $this->getValue() !== true) {
      $this->addErrorCode('error_field_required');
      return false;
    }

    return true;
  }
}
