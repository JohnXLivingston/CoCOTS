<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

class CocotsExamplePresets extends CocotsPresets {
  public function websiteTypes() {
    return array(
      array(
        'value' => 'website_type_1',
        'label' => $this->app->loc->translate('website_type_1')
      ),
      array(
        'value' => 'website_type_2',
        'label' => $this->app->loc->translate('website_type_2')
      )
    );
  }

  public function websitePlugins() {
    return array(
      array(
        'value' => 'website_plugin_1',
        'label' => $this->app->loc->translate('website_plugin_1'),
        'default' => true
      ),
      array(
        'value' => 'website_plugin_2',
        'label' => $this->app->loc->translate('website_plugin_2')
      ),
      array(
        'value' => 'website_plugin_3',
        'label' => $this->app->loc->translate('website_plugin_3'),
        'disabled' => true,
        'default' => true
      ),
      array(
        'value' => 'website_plugin_4',
        'label' => $this->app->loc->translate('website_plugin_4'),
        'disabled' => true,
        'default' => false
      )
    );
  }

  public function activateAccount($account) {
    error_log('Activating account ' . $account['name']);
    return true;
  }

  public function disableAccount($account) {
    error_log('Disabling account ' . $account['name']);
    return true;
  }
}
