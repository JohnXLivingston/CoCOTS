<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

class Application {
  public $loc;
  public $presets;
  public $db;
  public $accounts;
  public $moderators;
  public $terms;
  public $debug_mode = false;

  public function __construct() {
    if (COCOTS_ENABLE_DEBUG && ($_GET['debug'] ?? '') === '1') {
      $this->debug_mode = true;
    }
    $this->loc = new I18n(COCOTS_DEFAULT_LANGUAGE);
    $this->accounts = new Accounts($this);
    $this->moderators = new Moderators($this);
    $this->terms = new Terms($this);
    $this->loadPresets();
  }

  public function getBaseUrl() {
    return '' . COCOTS_URL;
  }

  public function getDomain() {
    $url = parse_url($this->getBaseUrl());
    return $url['host'];
  }

  public function isHttps() {
    return preg_match('/^https:/', $this->getBaseUrl());
  }

  public function getAdminUrl($sort_param = null, $filters_changes = null, $debug_mode = null, $send_test_mail = null) {
    $url = $this->getBaseUrl() . '/admin/';
    if (($this->debug_mode && !isset($debug_mode)) || $debug_mode) {
      $url.= '?debug=1&';
    }
    if (!isset($sort_param) && isset($_GET['sort'])) {
      $sort_param = $_GET['sort'];
    }
    if (isset($sort_param)) {
      $url.= substr($url, -1) === '&' ? '' : '?';
      $url.= 'sort=' . urlencode($sort_param) . '&';
    }

    $filters = isset($_GET['filters']) && !empty($_GET['filters']) ? explode(',', $_GET['filters']) : array();
    if (!empty($filters_changes)) {
      if (!is_array($filters_changes)) {
        $filters_changes = array($filters_changes);
      }
      foreach ($filters_changes as $filters_change) {
        if ($filters_change === '*') {
          $filters = [];
        } else if (substr($filters_change, 0, 1) === '+') {
          array_push($filters, substr($filters_change, 1));
        } else if (substr($filters_change, 0, 1) === '-') {
          $filters = array_diff($filters, [substr($filters_change, 1)]);
        }
      }
    }
    if (count($filters) > 0) {
      $url.= substr($url, -1) === '&' ? '' : '?';
      $url.= 'filters=' . urlencode(implode(',', $filters));
    }

    if (isset($send_test_mail)) {
      $url.= substr($url, -1) === '&' ? '' : '?';
      $url.= 'send_test_mail=1&';
    }
    return $url;
  }

  public function getInvitUrl($email, $key) {
    $url = $this->getBaseUrl() . '/admin/invit.php?';
    $url.= 'email=' . urlencode($email) . '&';
    $url.= 'key=' . urlencode($key) . '&';
    return $url;
  }

  public function getLogoutUrl() {
    $url = $this->getBaseUrl() . '/admin/?logout=1';
    if ($this->debug_mode) {
      $url.= '&debug=1';
    }
    return $url;
  }

  protected function loadPresets() {
    $path = COCOTS_PRESETS_PATH;
    if (!$path) {
      throw new Exception('Missing presset in config.');
    }
    require_once(COCOTS_ROOT_DIR . 'lib/presets/abstract.php');
    if (strpos($path, '/') !== false) {
      require_once(realpath($path));
    } else {
      require_once(COCOTS_ROOT_DIR . 'lib/presets/' . $path);
    }
    $classname = COCOTS_PRESETS_CLASS;
    $this->presets = new $classname($this);
  }

  public function connectToDB($migrate = false) {
    try {
      $this->db = new PDO(COCOTS_DB_PDO_STRING, COCOTS_DB_USER, COCOTS_DB_PASS);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->db->exec('SET CHARACTER SET utf8mb4');
    } catch (PDOException $e) {
      // To avoid DB params leak, throw another Exception.
      throw new Exception('Database connection error.');
    }
    $this->testDBVersion('cocots', 1, 'createTableVersion', $migrate);
    $this->testDBVersion('cocots_account', $this->accounts::DBVERSION, 'createTableAccount', $migrate);
    $this->testDBVersion('cocots_moderator', $this->moderators::DBVERSION, 'createTableModerator', $migrate);
    $this->testDBVersion('cocots_terms', $this->terms::DBVERSION, 'createTableTerms', $migrate);
  }

  protected function testDBVersion($name, $required_version, $method, $migrate = false) {
    try {
      $sql = 'SELECT `version` FROM `' . COCOTS_DB_PREFIX . 'version` WHERE ';
      $sql.= '`name` = :name';
      $sth = $this->db->prepare($sql);
      $sth->bindValue(':name', $name);
      $sth->execute();
    } catch (PDOException $e) {
      if ($name === 'cocots' && $e->getCode() === '42S02') { // ER_NO_SUCH_TABLE
        // This means that the DB is not created...
        $sth = null;
      } else {
        throw $e; // bubble exception.
      }
    }

    $version = 0;
    if ($sth) {
      $row = $sth->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        $version = intval($row['version']);
      }
    }

    if ($version === $required_version) {
      // Everything is fine.
      return;
    }
    if ($migrate) {
      $this->$method($version, $required_version);
      return;
    }

    error_log(
      'Database is not correctly initialized. ' .
      $name . ' should be in version ' . strval($required_version) . ' but is ' . strval($version)
    );
    throw new CocotsSmartException(
      'The database was not correctly installed.'
    );
  }

  protected function createTableVersion($current_version, $required_version) {
    if ($current_version === 0) {
      $sql = 'CREATE TABLE IF NOT EXISTS `' . COCOTS_DB_PREFIX . 'version` ( ';
      $sql.= ' `name` VARCHAR(20) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ';
      $sql.= ' `version` TINYINT(3) UNSIGNED NOT NULL, ';
      $sql.= ' PRIMARY KEY ( `name` ) ';
      $sql.= ' ) ';
      $sql.= ' ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ';
      $this->db->exec($sql);
  
      $this->setDBVersion('cocots', 1);
      $current_version = 1;
    }
    if ($required_version !== $current_version) {
      throw new Exception('Unknow required version for cocots');
    }
  }

  protected function createTableAccount($current_version, $required_version) {
    $this->accounts->createTable($current_version, $required_version);
  }

  protected function createTableModerator($current_version, $required_version) {
    $this->moderators->createTable($current_version, $required_version);
  }

  protected function createTableTerms($current_version, $required_version) {
    $this->terms->createTable($current_version, $required_version);
  }

  public function setDBVersion($name, $version) {
    $sql = 'INSERT `' . COCOTS_DB_PREFIX . 'version` ';
    $sql.= ' (`name`, `version`) VALUES ( :name, :version ) ';
    $sql.= ' ON DUPLICATE KEY UPDATE `version`=:version ';
    $sth = $this->db->prepare($sql);
    $sth->bindValue(':name', $name);
    $sth->bindValue(':version', $version);
    $sth->execute();
  }

  public function getForm($form) {
    if ($form === 'creation') {
      require(COCOTS_ROOT_DIR . 'lib/forms/creation.php');
      return new CreationForm($this);
    }
    if ($form === 'invit') {
      require(COCOTS_ROOT_DIR . 'lib/forms/invit.php');
      return new InvitForm($this);
    }
    throw new Exception('Invalid form name');
  }

  protected function getMailer() {
    // Including this lib only when necessary.
    require_once(COCOTS_ROOT_DIR . 'lib/mail.php');
    return getMailer();
  }

  public function notifyModerators($subject, $message) {
    $addresses = $this->getModeratorsMails();
    return $this->notify($addresses, $subject, $message);
  }

  public function notifyAdmins($subject, $message) {
    $addresses = $this->getAdminsMails();
    return $this->notify($addresses, $subject, $message);
  }

  public function notify($addresses, $subject, $message) {
    if (count($addresses) === 0) {
      return;
    }
    $mail = $this->getMailer();
    foreach ($addresses as $address) {
      $mail->addAddress($address);
    }
    $mail->Subject = (defined('COCOTS_MAIL_PREFIX') ? COCOTS_MAIL_PREFIX : '') . $subject;
    $mail->Body = $message;
    try {
      $mail->send();
    } catch (Exception $e) {
      error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}.");
      // Failing mail should not make fail the query.
    }
  }

  public function getModeratorsMails() {
    $admins_mails = $this->getAdminsMails();
    $moderators_mails = $this->moderators->getActiveModeratorsMails();
    return array_merge($admins_mails, $moderators_mails);
  }

  public function getAdminsMails() {
    if (!defined('COCOTS_MAIL_ADMINS') || !COCOTS_MAIL_ADMINS || !is_array(COCOTS_MAIL_ADMINS)) {
      return [];
    }
    return COCOTS_MAIL_ADMINS;
  }

  public function notifyAccountCreated($account, $subject, $message, $notif_recipients) {
    if ($notif_recipients === 'user') {
      $mail = $this->getMailer();
      $mail->addAddress($account['email']);
      $moderators_adresses = $this->getModeratorsMails();
      foreach ($moderators_adresses as $address) {
        $mail->addBCC($address);
      }
      $mail->Subject = (defined('COCOTS_MAIL_PREFIX') ? COCOTS_MAIL_PREFIX : '') . $subject;
      $mail->Body = $message;
      try {
        $mail->send();
      } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}.");
        // Failing mail should not make fail the query.
      }
    } else if ($notif_recipients === 'admins') {
      $this->notifyAdmins($subject, $message);
    } else if ($notif_recipients === 'moderators') {
      $this->notifyModerators($subject, $message);
    } else {
      error_log("Wrong configuration for COCOTS_ACCOUNT_CREATED_NOTIFICATION_RECIPIENTS, fallback on admins");
      $this->notifyAdmins($subject, $message);
    }
  }

  public function getCSSUrl() {
    $path = realpath(COCOTS_ROOT_DIR . 'static/styles.css');
    // Searching for a version with a md5 in filename...
    $file = '';
    $nb = 0;
    foreach (glob($path . '.md5*') as $filename) {
      $nb++;
      $file = basename($filename);
    }
    if ($nb > 1) {
      error_log('There are multiple css files, dont know which to choose.');
      $file = '';
    }
    if (empty($file)) {
      $file = 'styles.css';
    }
    $u = $this->getBaseUrl() . '/static/' . $file;
    return $u;
  }
}
