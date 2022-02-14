<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

class Terms {
  CONST DBVERSION = 1;
  protected $app;

  public function __construct($app) {
    $this->app = $app;
  }

  public function createTable($current_version, $required_version) {
    if ($current_version === 0) {
      $sql = 'CREATE TABLE IF NOT EXISTS `' . COCOTS_DB_PREFIX . 'terms` ( ';
      $sql.= ' `id` MEDIUMINT NOT NULL AUTO_INCREMENT, ';
      $sql.= ' `account_id` MEDIUMINT NOT NULL, ';
      $sql.= ' `term` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ';
      $sql.= ' `version` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ';
      $sql.= ' `date` DATETIME NOT NULL DEFAULT NOW(), ';
      $sql.= ' PRIMARY KEY ( `id` ), ';
      $sql.= ' INDEX ( `account_id`, `term`, `version` ) ';
      $sql.= ' ) ';
      $sql.= ' ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ';
      $this->app->db->exec($sql);

      $this->app->setDBVersion('cocots_terms', 1);
      $current_version = 1;
    }
    if ($required_version !== $current_version) {
      throw new Exception('Unknow required version for cocots_terms');
    }
  }

  public function create($account_id, $term, $version) {
    $columns = array(
      'account_id',
      'term',
      'version'
    );

    $sql = 'INSERT INTO `' . COCOTS_DB_PREFIX . 'terms` ';
    $sql.= ' ( `' . implode('`, `', $columns) . '` ) ';
    $sql.= ' VALUES ( :'. implode(', :', $columns) . ' ) ';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'account_id' => $account_id,
      'term' => $term,
      'version' => $version
    ));
    $id = $this->app->db->lastInsertId();

    return $id;
  }

  public function getById($id) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'terms` WHERE ';
    $sql.= '`id` = :id';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':id', $id, PDO::PARAM_STR);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    return $row;
  }

  public function getByAccountId($id) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'terms` WHERE ';
    $sql.= '`account_id` = :id';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':id', $id, PDO::PARAM_STR);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    return $row;
  }

}
