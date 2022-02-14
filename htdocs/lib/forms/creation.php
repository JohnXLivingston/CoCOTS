<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/abstract.php');

class CreationForm extends Form {
  protected $plugins_fields = array();
  protected $terms_fields = array();

  public function getFormName() {
    return 'creation';
  }

  protected function initFields() {
    $this->fields['website_title'] = new TextField($this, 'website_title', array(
      'required' => true,
      'autofocus' => true,
      'label' => $this->app->loc->translateSafe('website_title'),
      'placeholder' => true,
      'maxlength' => '128'
    ));

    $this->fields['website_name'] = new TextField($this, 'website_name', array(
      'required' => true,
      'label' => $this->app->loc->translateSafe('website_name'),
      'placeholder' => true,
      'pattern' => '[a-z0-9]{2,20}',
      'title' => $this->app->loc->translateSafe('website_name_constraints'),
      'aria-label' => $this->app->loc->translateSafe('website_name_constraints')
    ));

    if (defined('COCOTS_HOSTING_DOMAINS') && is_array(COCOTS_HOSTING_DOMAINS)) {
      $website_domain_options = array();
      foreach (COCOTS_HOSTING_DOMAINS as $dkey => $d) {
        array_push($website_domain_options, array(
          'label' => $d,
          'value' => $dkey
        ));
      }
      $this->fields['website_domain'] = new SelectField($this, 'website_domain', array(
        'required' => true,
        'label' => $this->app->loc->translateSafe('website_domain'),
        'options' => $website_domain_options,
        'hide_empty_value' => true
      ));

      // Default value:
      if (defined('COCOTS_HOSTING_DOMAIN')) {
        $default_website_domain = array_search(COCOTS_HOSTING_DOMAINS, $website_domain_options, true);
        if (!empty($default_website_domain)) {
          $this->fields['website_domain']->setValue($default_website_domain);
        }
      }
    }

    $this->fields['email'] = new EmailField($this, 'email', array(
      'required' => true,
      'label' => $this->app->loc->translateSafe('email'),
      'placeholder' => $this->app->loc->translateSafe('email_example')
    ));

    $this->fields['confirm_email'] = new EmailField($this, 'confirm_email', array(
      'required' => true,
      'label' => $this->app->loc->translateSafe('confirm_email'),
      'placeholder' => $this->app->loc->translateSafe('email_example')
    ));

    $website_types = $this->app->presets->websiteTypes();
    if ($website_types) {
      $this->fields['website_type'] = new SelectField($this, 'website_type', array(
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
        $this->fields[$fname] = new CheckboxField($this, $fname, array(
          'label' => $plugin['label'],
          'disabled' => boolval($plugin['disabled'] ?? false),
          'default' => boolval($plugin['default'] ?? false)
        ));
        array_push($this->plugins_fields, $this->fields[$fname]);
      }
    }

    if (defined('COCOTS_TERMS')) {
      if (!is_array(COCOTS_TERMS)) {
        throw new Exception('Invalid config COCOTS_TERMS');
      }
      foreach (COCOTS_TERMS as $terms_key => $terms) {
        if (!is_array($terms)) {
          throw new Exception('Invalid config COCOTS_TERMS for ' . $terms_key);
        }
        if (!preg_match('/^\w{1,255}$/', $terms_key)) {
          throw new Exception('Invalid config COCOTS_TERMS, terms key invalid: ' . $terms_key);
        }
        if (empty($terms['html']) || empty($terms['version']) || !preg_match('/^(\w|\.){1,255}$/', $terms_key)) {
          throw new Exception('Invalid config COCOTS_TERMS for ' . $terms_key);
        }
        $fname = 'terms_' . $terms_key;
        $this->fields[$fname] = new CheckboxField($this, $fname, array(
          'label' => $terms['html'],
          'checkbox_value' => $terms['version'],
          'required' => $terms['required'] ? true : false,
          'label_is_html' => true
        ));
        array_push($this->terms_fields, $this->fields[$fname]);
      }
    }

    if (defined('COCOTS_SECURITY_QUESTION')) {
      $this->fields['security_question'] = new TextField($this, 'security_question', array(
        'required' => true,
        'label' => strval(COCOTS_SECURITY_QUESTION),
        'placeholder' => true
      ));
    }
  }

  public function getPluginsFields() {
    return $this->plugins_fields;
  }

  public function getTermsFields() {
    return $this->terms_fields;
  }

  // protected function getWebsiteHostname() {
  //   $name_field = $this->fields['website_name']->getValue();
  //   if ($this->hasField('website_domain')) {
  //     $option = $this->fields['website_domain']->getSelectedOption();
  //     if ($option) {
  //       $domain = $option['label'];
  //     } else {
  //       throw new Exception('Option not found');
  //     }
  //   } else {
  //     $domain = COCOTS_HOSTING_DOMAIN;
  //   }
  //   if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
  //     throw new Exception('Missing or invalid config COCOTS_HOSTING_DOMAIN / COCOTS_HOSTING_DOMAINS');
  //   }
  //   return $name_field . '.' . $domain;
  // }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    if ($this->fields['email']->getValue() !== $this->fields['confirm_email']->getValue()) {
      $this->fields['email']->addErrorCode('error_confirm_email');
      $this->fields['confirm_email']->addErrorCode('error_confirm_email');
      return false;
    }

    if ($this->hasField('security_question')) {
      $valid_answers = defined('COCOTS_SECURITY_ANSWERS') ? COCOTS_SECURITY_ANSWERS : array();
      if (!in_array($this->fields['security_question']->getValue(), $valid_answers, true)) {
        error_log('COCOTS_BOT_ALERT_QUESTION: invalid security question answer: "' . $this->fields['security_question']->getValue() . '"');
        $this->fields['security_question']->addErrorCode('error_security_question');
        return false;
      }
    }

    $name = $this->fields['website_name']->getValue();

    $tmpurl = $name . '.example.com';
    if (!filter_var($tmpurl, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
      $this->fields['website_name']->addErrorCode('error_website_name_invalid');
      return false;
    }

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
      $title = $this->fields['website_title']->getValue();
      $name = $this->fields['website_name']->getValue();
      if ($this->hasField('website_domain')) {
        $domain_option = $this->fields['website_domain']->getSelectedOption();
        if (!$domain_option) {
          throw new Exception('getSelectedOption returned a falsey value.');
        }
        $domain = $domain_option['label'];
      } else {
        $domain = COCOTS_HOSTING_DOMAIN;
      }
      if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        throw new Exception('Missing or invalid config COCOTS_HOSTING_DOMAIN / COCOTS_HOSTING_DOMAINS: domain is invalid.');
      }
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
        'title' => $title,
        'name' => $name,
        'domain' => $domain,
        'email' => $email,
        'type' => $type,
        'plugins' => $plugins
      );

      $aid = $this->app->accounts->create($account_info);
      $terms_fields = $this->getTermsFields();
      if (count($terms_fields) > 0) {
        foreach ($terms_fields as $term_field) {
          $term_val = $term_field->getValue();
          if ($term_val !== false) {
            $term = preg_replace('/^terms_/', '', $term_field->getName());
            $this->app->terms->create($aid, $term, $term_val);
          }
        }
      }

      return true;
    } catch (Exception | Error $e) {
      // We don't want to loose the form content. So we are catching exceptions...
      error_log($e);
      return false;
    }
  }
}
