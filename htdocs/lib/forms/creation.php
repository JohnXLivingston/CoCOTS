<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/abstract.php');

class CreationForm extends Form {
  protected function initFields() {
    $this->fields['website_name'] = new TextField('website_name', array(
      'required' => true,
      'label' => $this->app->loc->translate('website_name'),
      'placeholder' => true
    ));

    $this->fields['email'] = new EmailField('email', array(
      'required' => true,
      'label' => $this->app->loc->translate('email'),
      'placeholder' => $this->app->loc->translate('email_example')
    ));

    $website_types = $this->app->presets->websiteTypes();
    if ($website_types) {
      $this->fields['website_type'] = new SelectField('website_type', array(
        'required' => true,
        'label' => $this->app->loc->translate('website_type'),
        'options' => $website_types
      ));
    }
  }
}
