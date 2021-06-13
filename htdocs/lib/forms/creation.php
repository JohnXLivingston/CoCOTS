<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/fields/class.php');

class CreationForm {
  private $app;
  private $fields = array();

  public function __construct($app) {
    $this->app = $app;
    $this->fields['website_name'] = new TextField('website_name', array(
      'required' => true,
      'label' => $this->app->loc->translate('website_name'),
      'placeholder' => true
    ));

    $this->fields['email'] = new EmailField('email', array(
      'required' => true,
      'label' => $this->app->loc->translate('website_name'),
      'placeholder' => $this->app->loc->translate('email_example')
    ));
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
