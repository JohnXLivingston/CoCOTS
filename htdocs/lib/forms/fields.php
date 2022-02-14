<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

abstract class Field {
  protected $name;
  protected $label;
  protected $value;
  protected $error_codes = array();
  protected $required = false;
  protected $label_is_html = false;
  protected $form;

  public function __construct($form, $name, $options) {
    $this->form = $form;
    $this->name = $name;
    $this->value = null;
    if (($options['required'] ?? false) === true) {
      $this->required = true;
    }
    if (isset($options['label'])) {
      $this->label = $options['label'];
    }
    if (isset($options['label_is_html']) && $options['label_is_html'] === true) {
      $this->label_is_html = true;
    }
  }

  public function getName() {
    return $this->name;
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

  public function getLabelHtml($classes = '') {
    $html = '<label ';
    $html.= ' for="' . htmlspecialchars($this->name) . '" ';
    if (!empty($classes)) {
      $html.= ' class="' .htmlspecialchars($classes). '"';
    }
    $html.= '>';
    if ($this->label_is_html) {
      $html.= $this->getLabel();
    } else {
      $html.= htmlspecialchars($this->getLabel());
    }
    $html.= '</label>';
    return $html;
  }

  protected function getHelpContent() {
    if (!defined('COCOTS_FIELDS_HELP')) { return null; }
    if (!is_array(COCOTS_FIELDS_HELP)) { return null; }
    $form_name = $this->form->getFormName();
    if (!array_key_exists($form_name, COCOTS_FIELDS_HELP)) { return null; }
    if (empty(COCOTS_FIELDS_HELP[$form_name])) { return null; }
    if (!array_key_exists($this->name, COCOTS_FIELDS_HELP[$form_name])) { return null; }
    if (empty(COCOTS_FIELDS_HELP[$form_name][$this->name])) { return null; }
    return COCOTS_FIELDS_HELP[$form_name][$this->name];
  }

  public function getHelpHtml($classes = 'form-text') {
    $help = $this->getHelpContent();
    if (empty($help)) { return ''; }
    $r = '<div ';
    $r.= 'id="' . $this->name . '__Help" ';
    $r.= 'class="' . htmlspecialchars($classes) . '" ';
    $r.= '>' . $help . '</div>'; // help can contain html
    return $r;
  }

  public function readPost() {
    $this->setValue($_POST[$this->name] ?? '');
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
    $attrs['class'] = '';
    if (count($this->error_codes) > 0) {
      $attrs['class'] = $attrs['class'] . ' is-invalid';
    }
    if (!empty($this->getHelpContent())) {
      $attrs['aria-describedby'] = $this->name . '__Help';
    }
    return $attrs;
  }

  public function attributesHtml($classes = '') {
    $attrs = $this->getAttributes();
    $html = '';
    foreach ($attrs as $key => $value) {
      $html.= ' ' . $key . '="' . htmlspecialchars($value);
      if ($key === 'class' && !empty($classes)) {
        $html.= ' ' . htmlspecialchars($classes);
      }
      $html.= '" ';
    }
    return $html;
  }

  abstract public function html($classes = '');

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
  protected $autofocus = false;

  public function __construct($form, $name, $options) {
    parent::__construct($form, $name, $options);
    if (isset($options['placeholder'])) {
      if ($options['placeholder'] === true) {
        // By default, we take the label as placeholder.
        $this->placeholder = $this->label;
      } else {
        $this->placeholder = $options['placeholder'];
      }
    }
    if (isset($options['autofocus']) && $options['autofocus'] === true) {
      $this->autofocus = true;
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
    $attrs['class'] = $attrs['class'] . ' form-control';
    if ($this->autofocus) {
      $attrs['autofocus'] = '';
    }
    foreach (array('placeholder', 'aria-label', 'title') as $f) {
      if (isset($this->$f)) {
        $attrs[$f] = $this->$f;
      }
    }
    return $attrs;
  }

  function html($classes = '') {
    return '<input ' . $this->attributesHtml($classes) . '>';
  }
}

class TextField extends InputField {
  protected $pattern = null;
  protected $maxlength = null;
  public function __construct($form, $name, $options) {
    parent::__construct($form, $name, $options);
    if (isset($options['pattern'])) {
      $this->pattern = $options['pattern'];
    }
    if (isset($options['maxlength'])) {
      $this->maxlength = $options['maxlength'];
    }
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['type'] = 'text';
    $attrs['value'] = $this->getValue();
    if (isset($this->pattern)) {
      $attrs['pattern'] = $this->pattern;
    }
    if (isset($this->maxlength)) {
      $attrs['maxlength'] = $this->maxlength;
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

    if ($this->maxlength) {
      $value = $this->getValue();
      $string = isset($value) ? strval($value) : '';
      if (!preg_match('/^.{0,' . $this->maxlength . '}$/', $string)) {
        $this->addErrorCode('error_field_maxlength');
        return false;
      }
    }

    return true;
  }
}

class PasswordField extends TextField {
  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['type'] = 'password';
    return $attrs;
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
  protected $hide_empty_value = true;

  public function __construct($form, $name, $options) {
    parent::__construct($form, $name, $options);
    if(is_array($options['options'])) {
      $this->options = $options['options'];
    }
    if (($options['hide_empty_value'] ?? false) === true) {
      $this->hide_empty_value = true;
    }
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['name'] = $this->name;
    $attrs['class'] = $attrs['class'] . ' form-select';
    return $attrs;
  }

  function html($classes = '') {
    $html = '<select ' . $this->attributesHtml($classes) . '>';
    if (!$this->hide_empty_value) {
      $html.= '<option value=""';
      if (!isset($this->value)) {
        $html.= ' selected="selected" ';
      }
      $html.= '></option>';
    }
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

  public function getSelectedOption() {
    foreach ($this->options as $idx => $option) {
      if (isset($this->value) && $this->value === $option['value']) {
        return $option;
      }
    }
    return null;
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
  protected $checkbox_value = true; // value of the checked field. If true, value will be false/true. If this is a string, will be false or 'string'.

  public function __construct($form, $name, $options) {
    parent::__construct($form, $name, $options);
    $this->value = false;
    if(isset($options['disabled'])) {
      $this->disabled = boolval($options['disabled']);
    }
    if(isset($options['default'])) {
      $this->default = boolval($options['default']);
    }
    if (isset($options['checkbox_value']) && is_string($options['checkbox_value'])) {
      $this->checkbox_value = $options['checkbox_value'];
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
    if ($this->checkbox_value !== true) {
      $this->value = $value === $this->checkbox_value ? $value : false;
    } else {
      $this->value = boolval($value);
    }
    return $this;
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['name'] = $this->name;
    $attrs['type'] = 'checkbox';
    if ($this->checkbox_value === true) {
      $attrs['value'] = '1';
    } else {
      $attrs['value'] = $this->checkbox_value;
    }
    $attrs['class'] = $attrs['class'] . ' form-check-input';
    if ($this->getValue()) {
      $attrs['checked'] = 'checked';
    }
    if ($this->disabled) {
      $attrs['disabled'] = 'disabled';
    }
    return $attrs;
  }

  function html($classes = '') {
    return '<input ' . $this->attributesHtml($classes) . '>';
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    if ($this->isRequired() && $this->getValue() !== $this->checkbox_value) {
      $this->addErrorCode('error_field_required');
      return false;
    }

    return true;
  }
}
