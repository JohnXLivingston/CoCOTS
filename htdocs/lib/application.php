<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

class Application {
  public $loc;
  public $presets;
  public $db;
  public $accounts;
  public $debug_mode = false;
  protected $admin = false; // Are we on an authenticated admin page?

  public function __construct($admin = false) {
    if (COCOTS_ENABLE_DEBUG && ($_GET['debug'] ?? '') === '1') {
      $this->debug_mode = true;
    }
    if ($admin === true) {
      $this->admin = true;
    }
    $this->loc = new I18n(COCOTS_DEFAULT_LANGUAGE);
    $this->accounts = new Accounts($this);
    $this->loadPresets();
    $this->connectToDB();
  }

  public function getBaseUrl() {
    return '' . COCOTS_URL;
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

  protected function connectToDB() {
    try {
      $this->db = new PDO(COCOTS_DB_PDO_STRING, COCOTS_DB_USER, COCOTS_DB_PASS);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->db->exec('SET CHARACTER SET utf8mb4');
    } catch (PDOException $e) {
      // To avoid DB params leak, throw another Exception.
      throw new Exception('Database connection error.');
    }
    $this->testDBVersion('cocots', 1, 'createTableVersion');
    $this->testDBVersion('cocots_account', 1, 'createTableAccount');
  }

  protected function testDBVersion($name, $required_version, $method) {
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
    if ($this->admin) {
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

  public function setDBVersion($name, $version) {
    $sql = 'INSERT `' . COCOTS_DB_PREFIX . 'version` ';
    $sql.= ' (`name`, `version`) VALUES ( :name, :version ) ';
    $sql.= ' ON DUPLICATE KEY UPDATE `version`=:version ';
    $sth = $this->db->prepare($sql);
    $sth->bindValue(':name', $name);
    $sth->bindValue(':version', 1);
    $sth->execute();
  }

  public function getForm($form) {
    if ($form === 'creation') {
      require(COCOTS_ROOT_DIR . 'lib/forms/creation.php');
      return new CreationForm($this);
    }
    throw new Exception('Invalid form name');
  }
}
