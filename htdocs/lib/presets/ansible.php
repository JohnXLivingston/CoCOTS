<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

abstract class CocotsAnsiblePresets extends CocotsPresets {
  public function websiteTypes() {
    return null;
  }

  public function websitePlugins() {
    return null;
  }

  public function checkConfig() {
    if (!parent::checkConfig()) {
      return false;
    }
    if (!defined('COCOTS_PRESETS_ANSIBLE_VAR_PATH')) {
      error_log('Missing constant COCOTS_PRESETS_ANSIBLE_VAR_PATH');
      return false;
    }
    if (!file_exists(COCOTS_PRESETS_ANSIBLE_VAR_PATH)) {
      error_log('Missing folder ' . COCOTS_PRESETS_ANSIBLE_VAR_PATH);
      return false;
    }
    if (!is_writable(COCOTS_PRESETS_ANSIBLE_VAR_PATH)) {
      error_log('Folder ' . COCOTS_PRESETS_ANSIBLE_VAR_PATH . ' is not writable');
      return false;
    }
    if (defined('COCOTS_PRESETS_ANSIBLE_NAME_PREFIX')) {
      if (!preg_match('/^[a-z]+_?$/', '' . COCOTS_PRESETS_ANSIBLE_NAME_PREFIX)) {
        error_log('Invalid COCOTS_PRESETS_ANSIBLE_NAME_PREFIX constant');
        return false;
      }
    }
    return true;
  }

  protected function writeAccountVars($account, $state) {
    $url = $account['name'] . '.' . $account['domain'];
    if (!filter_var($url, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
      error_log('Account url is not a valid domain name: "' . $url . '"');
      return false;
    }

    $file_name = COCOTS_PRESETS_ANSIBLE_VAR_PATH;
    if (substr($file_name, -1) !== '/') {
      $file_name.= '/';
    }
    $file_name.= $url . '.yml';

    $name_prefix = '';
    if (defined('COCOTS_PRESETS_ANSIBLE_NAME_PREFIX')) {
      $name_prefix = COCOTS_PRESETS_ANSIBLE_NAME_PREFIX;
    }

    $content = <<<EOF
mutu__users:
  - name: '{$name_prefix}{$account["name"]}'
    state: '$state'
    domains: [ '{$url}' ]
    spip: True

EOF;

    if (file_put_contents($file_name, $content) === false) {
      error_log('Error writing ' . $file_name);
      return false;
    }

    return true;
  }

  public function activateAccount($account) {
    $state = defined('COCOTS_PRESETS_ANSIBLE_STATE_ENABLED') ? COCOTS_PRESETS_ANSIBLE_STATE_ENABLED : 'enabled';
    return $this->writeAccountVars($account, $state);
  }

  public function disableAccount($account) {
    $state = defined('COCOTS_PRESETS_ANSIBLE_STATE_DISABLED') ? COCOTS_PRESETS_ANSIBLE_STATE_DISABLED : 'disabled';
    return $this->writeAccountVars($account, $state);
  }
}

class CocotsAnsibleSpipPresets extends CocotsAnsiblePresets {

}
