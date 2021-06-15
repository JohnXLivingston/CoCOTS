<?php

define('_COCOTS_INITIALIZED', true);
define('COCOTS_ROOT_DIR', realpath(__DIR__ . '/..') . '/');

require(COCOTS_ROOT_DIR . '../config/config.php');
require(COCOTS_ROOT_DIR . 'lib/i18n.php');

class Application {
  public $loc;
  public $presets;
  public $db;
  protected $admin = false; // Are we on an authenticated admin page?

  public function __construct($admin = false) {
    if ($admin === true) {
      $this->admin = true;
    }
    $this->loc = new I18n(COCOTS_DEFAULT_LANGUAGE);
    $this->loadPresets();
    $this->connectToDB();
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
      throw new Error('Database connection error.');
    }
    $this->testDB();
  }

  protected function testDB() {
    try {
      $sql = 'SELECT `version` FROM `' . COCOTS_DB_PREFIX . 'version` WHERE ';
      $sql.= '`application` = :application';
      $sth = $this->db->prepare($sql);
      $sth->execute(array(':application' => 'cocots'));
    } catch (PDOException $e) {
      if ($e->getCode() === '42S02') { // ER_NO_SUCH_TABLE
        // This means that the DB is not created...
        $sth = null;
      } else {
        throw $e; // bubble exception.
      }
    }

    $version = null;
    $row = $sth !== null ? $sth->fetch(PDO::FETCH_ASSOC) : false;
    if ($row && $row['version'] === '1') {
      return;
    }
    if ($this->admin) {
      $this->createDB();
      return;
    }
    throw new Error('Database is not correctly initialized.');
  }

  protected function createDB() {
    $sql = 'CREATE TABLE IF NOT EXISTS `' . COCOTS_DB_PREFIX . 'version` ( ';
    $sql.= ' `application` VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, ';
    $sql.= ' `version` TINYINT(3) UNSIGNED NOT NULL, ';
    $sql.= ' PRIMARY KEY ( `application` ) ';
    $sql.= ' ) ';
    $sql.= ' ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ';
    $this->db->exec($sql);

    $sql = 'INSERT IGNORE INTO `' . COCOTS_DB_PREFIX . 'version` ';
    $sql.= ' (`application`, `version`) VALUES ( :application, :version ) ';
    $sth = $this->db->prepare($sql);
    $sth->bindValue(':application', 'cocots');
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
