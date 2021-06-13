<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

abstract class Field {
  protected $name;
  protected $label;
  protected $value;
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
}

abstract class InputField extends Field {
  protected $placeholder = null;

  public function __construct($name, $options) {
    parent::__construct($name, $options);
    if(isset($options['placeholder'])) {
      if ($options['placeholder'] === true) {
        // By default, we take the label as placeholder.
        $this->placeholder = $this->label;
      } else {
        $this->placeholder = $options['placeholder'];
      }
    }
  }

  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['name'] = $this->name;
    if (isset($this->placeholder)) {
      $attrs['placeholder'] = $this->placeholder;
    }
    return $attrs;
  }

  function html() {
    return '<input ' . $this->attributesHtml() . '>';
  }
}

class TextField extends InputField {
  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['type'] = 'text';
    $attrs['value'] = $this->getValue();
    return $attrs;
  }
}

class EmailField extends TextField {
  public function getAttributes() {
    $attrs = parent::getAttributes();
    $attrs['type'] = 'email';
    return $attrs;
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
}
