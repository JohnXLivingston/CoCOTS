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

    return 'waiting';
  }

  public function activateAccount($account) {
    $state = defined('COCOTS_PRESETS_ANSIBLE_STATE_ENABLED') ? COCOTS_PRESETS_ANSIBLE_STATE_ENABLED : 'enabled';
    return $this->writeAccountVars($account, $state);
  }

  public function disableAccount($account) {
    $state = defined('COCOTS_PRESETS_ANSIBLE_STATE_DISABLED') ? COCOTS_PRESETS_ANSIBLE_STATE_DISABLED : 'disabled';
    return $this->writeAccountVars($account, $state);
  }

  protected function ansibleProcessingResultDir($account) {
    $url = $account['name'] . '.' . $account['domain'];
    $dir = COCOTS_PRESETS_ANSIBLE_VAR_PATH;
    if (substr($dir, -1) !== '/') {
      $dir.= '/';
    }
    $dir.= $url . '/';
    return $dir;
  }

  public function checkAccountProcessing($account) {
    $status = $account['status'];
    if ($status === 'processing' || $status === 'processing_disabled') {
      // We have to check for the «ansible_status» file, and check its content.
      $status_file_name = $this->ansibleProcessingResultDir($account) . 'ansible_status';
      if (!file_exists($status_file_name)) {
        return 'waiting';
      }
      $code = file_get_contents($status_file_name);
      $code = str_replace(array("\r", "\n"), '', $code);
      if ($code !== '0') {
        error_log('Status code for file ' . $status_file_name . ' is ' . $code);
        return false;
      }
      return true;
    } elseif ($status === 'processing_deleted') {
      error_log('Not Implemented Yet: account deletion');
      return false;
    } else {
      error_log('Calling checkAccountProcessing on account ' . $account['id'] . ' which is in an unattended status ' . $status);
      return false;
    }
  }

  public function resetAccountProcessing($account) {
    $dir = $this->ansibleProcessingResultDir($account);
    $timestamp = time();
    foreach (array('ansible_status', 'ansible_output') as $filename) {
      if (file_exists($dir . $filename)) {
        rename($dir . $filename, $dir . $filename . '.' . $timestamp);
      }
    }
    return true;
  }
}

class CocotsAnsibleSpipPresets extends CocotsAnsiblePresets {

}
