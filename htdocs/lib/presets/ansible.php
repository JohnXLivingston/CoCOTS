<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

const YAML_ESCAPEES = ['\\', '\\\\', '\\"', '"',
  "\x00",  "\x01",  "\x02",  "\x03",  "\x04",  "\x05",  "\x06",  "\x07",
  "\x08",  "\x09",  "\x0a",  "\x0b",  "\x0c",  "\x0d",  "\x0e",  "\x0f",
  "\x10",  "\x11",  "\x12",  "\x13",  "\x14",  "\x15",  "\x16",  "\x17",
  "\x18",  "\x19",  "\x1a",  "\x1b",  "\x1c",  "\x1d",  "\x1e",  "\x1f",
  "\x7f",
  "\xc2\x85", "\xc2\xa0", "\xe2\x80\xa8", "\xe2\x80\xa9",
];
const YAML_ESCAPED = ['\\\\', '\\"', '\\\\', '\\"',
  '\\0',   '\\x01', '\\x02', '\\x03', '\\x04', '\\x05', '\\x06', '\\a',
  '\\b',   '\\t',   '\\n',   '\\v',   '\\f',   '\\r',   '\\x0e', '\\x0f',
  '\\x10', '\\x11', '\\x12', '\\x13', '\\x14', '\\x15', '\\x16', '\\x17',
  '\\x18', '\\x19', '\\x1a', '\\e',   '\\x1c', '\\x1d', '\\x1e', '\\x1f',
  '\\x7f',
  '\\N', '\\_', '\\L', '\\P',
];

function escapeYaml($string) {
  return sprintf('"%s"', str_replace(YAML_ESCAPEES, YAML_ESCAPED, $string));
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
      // max length 10: max unix username lenght is 32. So website_name(max 20) + 10 + _(1) < 32
      if (!preg_match('/^[a-z]{1,10}_?$/', '' . COCOTS_PRESETS_ANSIBLE_NAME_PREFIX)) {
        error_log('Invalid COCOTS_PRESETS_ANSIBLE_NAME_PREFIX constant');
        return false;
      }
    }
    if (defined('COCOTS_PRESETS_ANSIBLE_USE_DOMAIN_KEY_AS_PREFIX') && COCOTS_PRESETS_ANSIBLE_USE_DOMAIN_KEY_AS_PREFIX) {
      if (defined('COCOTS_HOSTING_DOMAINS') && is_array(COCOTS_HOSTING_DOMAINS)) {
        foreach (COCOTS_HOSTING_DOMAINS as $key => $domain) {
          // max length 10: max unix username lenght is 32. So website_name(max 20) + 10 + _(1) < 32
          if (!preg_match('/^[a-z]{1,10}_?$/', '' . $key)) {
            error_log('Invalid value "'.$key.'" in COCOTS_PRESETS_ANSIBLE_NAME_PREFIX\'s keys.');
            return false;
          }
        }
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
    if (defined('COCOTS_PRESETS_ANSIBLE_USE_DOMAIN_KEY_AS_PREFIX') && COCOTS_PRESETS_ANSIBLE_USE_DOMAIN_KEY_AS_PREFIX) {
      if (defined('COCOTS_HOSTING_DOMAINS') && is_array(COCOTS_HOSTING_DOMAINS)) {
        foreach (COCOTS_HOSTING_DOMAINS as $key => $val) {
          if ($val === $account['domain']) {
            $name_prefix = $key;
            break;
          }
        }
      }
    }
    if ($name_prefix === '' && defined('COCOTS_PRESETS_ANSIBLE_NAME_PREFIX')) {
      $name_prefix = COCOTS_PRESETS_ANSIBLE_NAME_PREFIX;
    }

    $email_escaped = escapeYaml($account['email']);

    $spip_branch_line = '';
    if (defined('COCOTS_PRESETS_ANSIBLE_SPIP_BRANCH')) {
      $spip_branch_line = "branch: '" . str_replace("'", "''", COCOTS_PRESETS_ANSIBLE_SPIP_BRANCH) . "'";
    }

    $spip_depots = '';
    if (defined('COCOTS_PRESETS_ANSIBLE_SPIP_DEPOTS')) {
      $depots_escaped = [];
      foreach (COCOTS_PRESETS_ANSIBLE_SPIP_DEPOTS as $depot) {
        array_push($depots_escaped, escapeYaml($depot));
      }
      $spip_depots = "depots: [" . implode(', ', $depots_escaped) . "]";
    }
    $spip_plugins = '';
    if (defined('COCOTS_PRESETS_ANSIBLE_SPIP_PLUGINS')) {
      $plugins_escaped = [];
      foreach (COCOTS_PRESETS_ANSIBLE_SPIP_PLUGINS as $plugin) {
        array_push($plugins_escaped, escapeYaml($plugin));
      }
      $spip_plugins = "plugins: [" . implode(', ', $plugins_escaped) . "]";
    }

    $spip_config = array();
    $write_spip_config = false;
    if (defined('COCOTS_PRESETS_ANSIBLE_SPIP_CONFIG') && is_array(COCOTS_PRESETS_ANSIBLE_SPIP_CONFIG)) {
      $write_spip_config = true;
      $spip_config = array_merge($spip_config, COCOTS_PRESETS_ANSIBLE_SPIP_CONFIG);
    }
    if (defined('COCOTS_PRESETS_ANSIBLE_SPIP_USE_COCOTS_SMTP') && COCOTS_PRESETS_ANSIBLE_SPIP_USE_COCOTS_SMTP) {
      $write_spip_config = true;
      $spip_config['facteur_smtp'] = 'oui';
      $spip_config['facteur_adresse_envoi'] = 'oui';
      $spip_config['facteur_adresse_envoi_email'] = ''.COCOTS_MAIL_FROM;
      $spip_config['facteur_smtp_host'] = ''.COCOTS_MAIL_SMTP_HOST;
      $spip_config['facteur_smtp_port'] = ''.COCOTS_MAIL_SMTP_PORT;
      $spip_config['facteur_smtp_auth'] = 'non';
      $spip_config['facteur_smtp_secure'] = 'non';
      if (defined('COCOTS_MAIL_SMTP_AUTH') && COCOTS_MAIL_SMTP_AUTH) {
        $spip_config['facteur_smtp_auth'] = 'oui';
        $spip_config['facteur_smtp_username'] = ''.COCOTS_MAIL_SMTP_AUTH_USER;
        $spip_config['facteur_smtp_password'] = ''.COCOTS_MAIL_SMTP_AUTH_PASS;
      }
    }

    $title_config_escaped = escapeYaml(json_encode(array('nom_site' => $account['title'])));

    $sftp = 'False';
    if (defined('COCOTS_PRESETS_ANSIBLE_SFTP') && COCOTS_PRESETS_ANSIBLE_SFTP === true) {
      $sftp = 'True';
    }

    $content = <<<EOF
mutu__users:
  - name: '{$name_prefix}{$account["name"]}'
    state: '$state'
    domains: [ '{$url}' ]
    spip: True
    sftp: {$sftp}
    site_options:
      {$spip_branch_line}
      {$spip_depots}
      {$spip_plugins}
      admin:
        name: 'Admin'
        login: 'admin'
        email: {$email_escaped}
      config:
        - name: cocots_nom_site
          rawjson: {$title_config_escaped}

EOF;
    if ($write_spip_config) {
      $spip_config = json_encode($spip_config);
      $spip_config_escaped = escapeYaml($spip_config);
      $content .= <<<EOF
        - name: cocots_config
          rawjson: {$spip_config_escaped}

EOF;
    }

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

  public function deleteAccount($account) {
    $state = defined('COCOTS_PRESETS_ANSIBLE_STATE_DELETED') ? COCOTS_PRESETS_ANSIBLE_STATE_DELETED : 'deleted';
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
    if ($status === 'processing' || $status === 'processing_disabled' || $status === 'processing_deleted') {
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
        if (!rename($dir . $filename, $dir . $filename . '.' . $timestamp)) {
          error_log('Failed renaming file ' . $dir . $filename . ' to ' . $dir . $filename . '.' . $timestamp);
          return false;
        }
      }
    }
    return true;
  }
}

class CocotsAnsibleSpipPresets extends CocotsAnsiblePresets {

}
