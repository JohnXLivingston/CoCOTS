<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/abstract.php');

class CreationForm extends Form {
  protected $plugins_fields = array();

  protected function initFields() {
    $this->fields['website_name'] = new TextField('website_name', array(
      'required' => true,
      'label' => $this->app->loc->translate('website_name'),
      'placeholder' => true,
      'pattern' => '[a-z0-9]{3,40}',
      'title' => $this->app->loc->translate('website_name_constraints'),
      'aria-label' => $this->app->loc->translate('website_name_constraints')
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

    $website_plugins = $this->app->presets->websitePlugins();
    if ($website_plugins) {
      foreach($website_plugins as $idx => $plugin) {
        if (!preg_match('/^\w+$/', $plugin['value'])) {
          throw new Exception('Invalid plugin name: ' . $plugin['value']);
        }
        $fname = 'plugin_' . $plugin['value'];
        $this->fields[$fname] = new CheckboxField($fname, array(
          'label' => $plugin['label'],
          'disabled' => boolval($plugin['disabled']),
          'default' => boolval($plugin['default'])
        ));
        array_push($this->plugins_fields, $this->fields[$fname]);
      }
    }
  }

  public function getPluginsFields() {
    return $this->plugins_fields;
  }
}
