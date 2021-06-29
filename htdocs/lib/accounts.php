<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

class Accounts {
  CONST DBVERSION = 2;
  protected $app;

  public function __construct($app) {
    $this->app = $app;
  }

  public function createTable($current_version, $required_version) {
    if ($current_version === 0) {
      $sql = 'CREATE TABLE IF NOT EXISTS `' . COCOTS_DB_PREFIX . 'account` ( ';
      $sql.= ' `id` MEDIUMINT NOT NULL AUTO_INCREMENT, ';
      $sql.= ' `name` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ';
      $sql.= ' `domain` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL, ';
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
      // - failed, failed_disabled, failed_deleted: operation failed.
      $sql.= ' `status` VARCHAR(40) DEFAULT \'waiting\', ';
      $sql.= ' `creation_date` DATETIME NOT NULL DEFAULT NOW(), ';
      $sql.= ' `activation_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `deactivation_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `deletion_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `rejection_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `activation_mail_sent` BOOLEAN NOT NULL DEFAULT FALSE, ';
      $sql.= ' PRIMARY KEY ( `id` ), ';
      $sql.= ' UNIQUE INDEX ( `name` ) ';
      $sql.= ' ) ';
      $sql.= ' ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ';
      $this->app->db->exec($sql);

      $this->app->setDBVersion('cocots_account', 2);
      $current_version = 2;
    }
    if ($current_version === 1) {
      $sql = 'ALTER TABLE `' . COCOTS_DB_PREFIX . 'account` ';
      $sql.= ' ADD COLUMN IF NOT EXISTS `rejection_date` DATETIME DEFAULT NULL AFTER `deletion_date` ';
      $this->app->db->exec($sql);

      $sql = 'ALTER TABLE `' . COCOTS_DB_PREFIX . 'account` ';
      $sql.= ' ADD COLUMN IF NOT EXISTS `activation_mail_sent` BOOLEAN NOT NULL DEFAULT FALSE AFTER `rejection_date` ';
      $this->app->db->exec($sql);

      $this->app->setDBVersion('cocots_account', 2);
      $current_version = 2;
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

  public function getByStatus($status) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'account` WHERE ';
    $sql.= '`status` = :status';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':status', $status, PDO::PARAM_STR);
    $sth->execute();
    return $sth->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getByStatusLike($status_like) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'account` WHERE ';
    $sql.= '`status` LIKE :status';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':status', $status_like, PDO::PARAM_STR);
    $sth->execute();
    return $sth->fetchAll(PDO::FETCH_ASSOC);
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
      'domain',
      'email',
      'type',
      'plugins'
    );
    $sql = 'INSERT INTO `' . COCOTS_DB_PREFIX . 'account` ';
    $sql.= ' ( `' . implode('`, `', $columns) . '` ) ';
    $sql.= ' VALUES ( :'. implode(', :', $columns) . ' ) ';
    $sth = $this->app->db->prepare($sql);
    $sth->execute($account_info);

    $message = '';
    $message.= $this->app->loc->translateSafe('new_account_message') . "\n\n";

    $message.= $this->app->loc->translateSafe('website_name') . ': ' . $account_info['name'] . "\n";
    $message.= $this->app->loc->translateSafe('website_domain') . ': ' . $account_info['domain'] . "\n\n";

    $message.= $this->app->getBaseUrl() . '/admin' . "\n\n";
    $message.= $this->app->loc->translateSafe('new_account_signature') . "\n\n";
    $this->app->notifyAdmins($this->app->loc->translateSafe('new_account_subject'), $message);
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

  protected function _updateActivationMailSent($id, $bool) {
    $sql = 'UPDATE `' . COCOTS_DB_PREFIX . 'account` ';
    $sql.= ' SET `activation_mail_sent`=:activation_mail_sent ';
    $sql.= ' WHERE `id`=:id';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'id' => $id,
      'activation_mail_sent' => $bool ? true : false
    ));
  }
  
  public function activate($id) {
    $account = $this->getById($id);
    if (!$account) {
      error_log('Account ' . $id . ' not found.');
      return false;
    }

    if (!in_array($account['status'], array('waiting', 'disabled', 'active'), true)) {
      error_log('Cant activate account ' . $account['id'] . ' because its status is ' . $account['status']);
      return false;
    }

    if (!$this->app->presets->checkConfig()) {
      error_log('The preset is not correctly configured.');
      return false;
    }

    if (!$this->app->presets->resetAccountProcessing($account)) {
      error_log('Failed to reset the account processing for account ' . $account['id']);
      return false;
    }
    
    $this->_updateStatus($id, 'processing');

    $return = $this->app->presets->activateAccount($account);
    if (!$return) {
      error_log('Failed to activate the account ' . $id);
      return false;
    }

    if ($return !== 'waiting') {
      $this->_updateStatus($id, 'active', 'activation_date');
    }

    return true;
  }

  public function disable($id) {
    $account = $this->getById($id);
    if (!$account) {
      error_log('Account ' . $id . ' not found.');
      return false;
    }

    if (!in_array($account['status'], array('active', 'disabled'), true)) {
      error_log('Cant disable account ' . $account['id'] . ' because its status is ' . $account['status']);
      return false;
    }

    if (!$this->app->presets->checkConfig()) {
      error_log('The preset is not correctly configured.');
      return false;
    }

    if (!$this->app->presets->resetAccountProcessing($account)) {
      error_log('Failed to reset the account processing for account ' . $account['id']);
      return false;
    }

    $this->_updateStatus($id, 'processing_disabled');

    $return = $this->app->presets->disableAccount($account);
    if (!$return) {
      error_log('Failed to disable the account ' . $id);
      return false;
    }

    if ($return !== 'waiting') {
      $this->_updateStatus($id, 'disabled', 'deactivation_date');
    }

    return true;
  }

  public function reject($id) {
    $account = $this->getById($id);
    if (!$account) {
      error_log('Account ' . $id . ' not found.');
      return false;
    }

    if (!in_array($account['status'], array('waiting'), true)) {
      error_log('Cant reject account ' . $account['id'] . ' because its status is ' . $account['status']);
      return false;
    }

    $this->_updateStatus($id, 'rejected', 'rejection_date');

    return true;
  }

  public function checkProcessing($account) {
    $id = $account['id'];
    $return = $this->app->presets->checkAccountProcessing($account);
    if ($return === 'waiting') {
      // we have to wait...
      return 'waiting';
    }
    if (!$return) {
      // It failed!
      error_log('Checking this account processing state has failed: ' . $account['id']);
      $failed_status = str_replace('processing', 'failed', $account['status']);
      $this->_updateStatus($id, $failed_status);
      
      $message = '';
      $message.= $this->app->loc->translateSafe('failed_account_message') . "\n\n";

      $message.= $this->app->loc->translateSafe('website_name') . ': ' . $account['name'] . "\n";
      $message.= $this->app->loc->translateSafe('website_domain') . ': ' . $account['domain'] . "\n\n";
      $message.= $this->app->loc->translateSafe('account_status') . ': ' . $failed_status . "\n\n";

      $message.= $this->app->getBaseUrl() . '/admin' . "\n\n";
      $message.= $this->app->loc->translateSafe('failed_account_signature') . "\n\n";
      $this->app->notifyAdmins($this->app->loc->translateSafe('failed_account_subject'), $message);

      return false;
    }

    if ($account['status'] === 'processing') {
      $this->_updateStatus($id, 'active', 'activation_date');

      // Check if it is a reactivation. Don't send the mail twice.
      if (!$account['activation_mail_sent']) {
        $subject = $this->app->loc->translateSafe('account_created_subject');
        $message = '';
        $message.= $this->app->loc->translateSafe('account_created_message') . "\n";
        $message.= 'https://' . $account['name'] . '.' . $account['domain'] . "\n\n";
        $message.= $this->app->loc->translateSafe('account_created_signature') . "\n";
        $this->app->notifyAccountCreated($account, $subject, $message);

        $this->_updateActivationMailSent($account['id'], true);
      }

    } elseif ($account['status'] === 'processing_disabled') {
      $this->_updateStatus($id, 'disabled', 'deactivation_date');

      $message = '';
      $message.= $this->app->loc->translateSafe('disabled_account_message') . "\n\n";

      $message.= $this->app->loc->translateSafe('website_name') . ': ' . $account['name'] . "\n";
      $message.= $this->app->loc->translateSafe('website_domain') . ': ' . $account['domain'] . "\n\n";

      $message.= $this->app->getBaseUrl() . '/admin' . "\n\n";
      $message.= $this->app->loc->translateSafe('disabled_account_signature') . "\n\n";
      $this->app->notifyAdmins($this->app->loc->translateSafe('disabled_account_subject'), $message);
    } elseif ($account['status'] === 'processing_deleted' ) {
      $this->_updateStatus($id, 'deleted', 'deletion_date');

      $message = '';
      $message.= $this->app->loc->translateSafe('deleted_account_message') . "\n\n";

      $message.= $this->app->loc->translateSafe('website_name') . ': ' . $account['name'] . "\n";
      $message.= $this->app->loc->translateSafe('website_domain') . ': ' . $account['domain'] . "\n\n";

      $message.= $this->app->getBaseUrl() . '/admin' . "\n\n";
      $message.= $this->app->loc->translateSafe('deleted_account_signature') . "\n\n";
      $this->app->notifyAdmins($this->app->loc->translateSafe('deleted_account_subject'), $message);
    } else {
      error_log('Dont know the status ' . $account['status']);
      return false;
    }

    return true;
  }
}
