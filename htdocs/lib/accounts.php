<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

class Accounts {
  protected $app;

  public function __construct($app) {
    $this->app = $app;
  }

  public function createTable($current_version, $required_version) {
    if ($current_version === 0) {
      $sql = 'CREATE TABLE IF NOT EXISTS `' . COCOTS_DB_PREFIX . 'account` ( ';
      $sql.= ' `id` MEDIUMINT NOT NULL AUTO_INCREMENT, ';
      $sql.= ' `name` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ';
      $sql.= ' `email` VARCHAR(255) NOT NULL, ';
      $sql.= ' `type` VARCHAR(255) DEFAULT NULL, ';
      $sql.= ' `plugins` JSON DEFAULT \'[]\', ';
      $sql.= ' `status` VARCHAR(20) DEFAULT \'waiting\', '; // waiting | active | disabled | deleted
      $sql.= ' `creation_date` DATETIME NOT NULL DEFAULT NOW(), ';
      $sql.= ' `activation_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `deactivation_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `deletion_date` DATETIME DEFAULT NULL, ';
      $sql.= ' PRIMARY KEY ( `id` ), ';
      $sql.= ' UNIQUE INDEX ( `name` ) ';
      $sql.= ' ) ';
      $sql.= ' ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ';
      $this->app->db->exec($sql);

      $this->app->setDBVersion('cocots_account', 1);
      $current_version = 1;
    }
    if ($required_version !== $current_version) {
      throw new Error('Unknow required version for cocots_account');
    }
  }

  public function getByName($name) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'account` WHERE ';
    $sql.= '`name` = :name';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':name', $name, PDO::PARAM_STR);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    return $row;
  }

  // public function create($account_info) {
  //   $fields = array();
  //   $sql = 'INSERT INTO `' . COCOTS_DB_PREFIX . 'account` ';
  //   $sql.= ' ( `' . implode('`, `', $fields) . '` ) ';
  //   $sql.= ' VALUES ( :'. implode(', :', $fields) . ' ) ';
  //   $sth = $this->db->app->prepare($sql);

  // }
}
