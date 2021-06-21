<?php

if(!defined('_COCOTS_INITIALIZED')) {
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
      // Status:
      // - waiting: account request just received
      // - processing: temporary status while account is activated
      // - active: account is active
      // - processing_disabled: temporary status while account is being deactivated
      // - disabled: account has been deactivated
      // - processing_deleted: temporary status while account is being deleted
      // - deleted: account has been removed
      // - rejected: account has been rejected, and never activated
      $sql.= ' `status` VARCHAR(40) DEFAULT \'waiting\', ';
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
      throw new Exception('Unknow required version for cocots_account');
    }
  }

  public function getById($id) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'account` WHERE ';
    $sql.= '`id` = :id';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'id' => $id
    ));
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    return $row;
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

  public function list() {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'account` ';
    $sql.= ' ORDER BY `name` ';
    $sth = $this->app->db->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $rows;
  }

  public function create($account_info) {
    $columns = array(
      'name',
      'email',
      'type',
      'plugins'
    );
    $sql = 'INSERT INTO `' . COCOTS_DB_PREFIX . 'account` ';
    $sql.= ' ( `' . implode('`, `', $columns) . '` ) ';
    $sql.= ' VALUES ( :'. implode(', :', $columns) . ' ) ';
    $sth = $this->app->db->prepare($sql);
    $sth->execute($account_info);
  }

  protected function _updateStatus($id, $status, $date_field=null) {
    $sql = 'UPDATE `' . COCOTS_DB_PREFIX . 'account` ';
    $sql.= ' SET `status`=:status ';
    if ($date_field) {
      $sql.= ' , `' . $date_field . '`=NOW() ';
    }
    $sql.= ' WHERE `id`=:id';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'id' => $id,
      'status' => $status
    ));
  }
  
  public function activate($id) {
    $account = $this->getById($id);
    if (!$account) {
      error_log('Account ' . $id . ' not found.');
      return false;
    }

    if ($account['status'] !== 'waiting' && $account['status'] !== 'disabled') {
      error_log('Cant activate account ' . $account['id'] . ' because its status is ' . $account['status']);
      return false;
    }

    if (!$this->app->presets->checkConfig()) {
      error_log('The preset is not correctly configured.');
      return false;
    }

    $this->_updateStatus($id, 'processing');

    if (!$this->app->presets->activateAccount($account)) {
      error_log('Failed to activate the account ' . $id);
      return false;
    }

    $this->_updateStatus($id, 'active', 'activation_date');

    return true;
  }

  public function disable($id) {
    $account = $this->getById($id);
    if (!$account) {
      error_log('Account ' . $id . ' not found.');
      return false;
    }

    if ($account['status'] !== 'active') {
      error_log('Cant disable account ' . $account['id'] . ' because its status is ' . $account['status']);
      return false;
    }

    if (!$this->app->presets->checkConfig()) {
      error_log('The preset is not correctly configured.');
      return false;
    }

    $this->_updateStatus($id, 'processing_disabled');

    if (!$this->app->presets->disableAccount($account)) {
      error_log('Failed to disable the account ' . $id);
      return false;
    }

    $this->_updateStatus($id, 'disabled', 'deactivation_date');

    return true;
  }
}
