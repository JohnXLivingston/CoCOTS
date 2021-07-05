<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/abstract.php');

class CreationForm extends Form {
  protected $plugins_fields = array();

  protected function initFields() {
    $this->fields['website_name'] = new TextField('website_name', array(
      'required' => true,
      'autofocus' => true,
      'label' => $this->app->loc->translateSafe('website_name'),
      'placeholder' => true,
      'pattern' => '[a-z0-9]{3,40}',
      'title' => $this->app->loc->translateSafe('website_name_constraints'),
      'aria-label' => $this->app->loc->translateSafe('website_name_constraints')
    ));

    $this->fields['email'] = new EmailField('email', array(
      'required' => true,
      'label' => $this->app->loc->translateSafe('email'),
      'placeholder' => $this->app->loc->translateSafe('email_example')
    ));

    $website_types = $this->app->presets->websiteTypes();
    if ($website_types) {
      $this->fields['website_type'] = new SelectField('website_type', array(
        'required' => true,
        'label' => $this->app->loc->translateSafe('website_type'),
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
          'disabled' => boolval($plugin['disabled'] ?? false),
          'default' => boolval($plugin['default'] ?? false)
        ));
        array_push($this->plugins_fields, $this->fields[$fname]);
      }
    }
  }

  public function getPluginsFields() {
    return $this->plugins_fields;
  }

  protected function getWebsiteHostname() {
    $name_field = $this->fields['website_name']->getValue();
    if (!filter_var(COCOTS_HOSTING_DOMAIN, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
      throw new Exception('Missing or invalid config COCOTS_HOSTING_DOMAIN');
    }
    return $name_field . '.' . COCOTS_HOSTING_DOMAIN;
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    $name = $this->fields['website_name']->getValue();

    if (defined('COCOTS_RESERVED_NAMES') && is_array(COCOTS_RESERVED_NAMES)) {
      if (in_array($name, COCOTS_RESERVED_NAMES)) {
        $this->fields['website_name']->addErrorCode('error_website_name_already_exists');
        return false;
      }
    }
    
    $existing_account = $this->app->accounts->getByName($name);
    if ($existing_account) {
      $this->fields['website_name']->addErrorCode('error_website_name_already_exists');
      return false;
    }
    return true;
  }

  public function save() {
    try {
      $name = $this->fields['website_name']->getValue();
      $domain = COCOTS_HOSTING_DOMAIN;
      $email = $this->fields['email']->getValue();
      if (isset($this->fields['website_type'])) {
        $type = $this->fields['website_type']->getValue();
      } else {
        $type = null;
      }
      $plugins_fields = $this->getPluginsFields();
      if (count($plugins_fields) > 0) {
        $a = array();
        foreach ($plugins_fields as $plugin_field) {
          if ($plugin_field->getValue()) {
            array_push($a, $plugin_field->getName());
          }
        }
        $plugins = json_encode($a);
      } else {
        $plugins = null;
      }
      $account_info = array(
        'name' => $name,
        'domain' => $domain,
        'email' => $email,
        'type' => $type,
        'plugins' => $plugins
      );

      $this->app->accounts->create($account_info);

      return true;
    } catch (Exception | Error $e) {
      // We don't want to loose the form content. So we are catching exceptions...
      error_log($e);
      return false;
    }
  }
}
