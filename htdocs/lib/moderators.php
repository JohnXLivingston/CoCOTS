<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

class Moderators {
  CONST DBVERSION = 1;
  protected $app;

  public function __construct($app) {
    $this->app = $app;
  }

  public function createTable($current_version, $required_version) {
    if ($current_version === 0) {
      $sql = 'CREATE TABLE IF NOT EXISTS `' . COCOTS_DB_PREFIX . 'moderator` ( ';
      $sql.= ' `id` MEDIUMINT NOT NULL AUTO_INCREMENT, ';
      $sql.= ' `email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ';
      $sql.= ' `type` VARCHAR(40) DEFAULT \'moderator\', ';
      /* Status:
      ∕* - waiting: waiting for invitation response
      /* - active
      /* - revoked
      */
      $sql.= ' `status` VARCHAR(40) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT \'waiting\' , ';
      $sql.= ' `password` VARCHAR(255) DEFAULT NULL, ';
      $sql.= ' `invitation` VARCHAR(255) DEFAULT NULL, ';
      $sql.= ' `creation_date` DATETIME NOT NULL DEFAULT NOW(), ';
      $sql.= ' `activation_date` DATETIME DEFAULT NULL, ';
      $sql.= ' `revocation_date` DATETIME DEFAULT NULL, ';
      $sql.= ' PRIMARY KEY ( `id` ), ';
      $sql.= ' INDEX ( `email`, `status` ) ';
      $sql.= ' ) ';
      $sql.= ' ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ';
      $this->app->db->exec($sql);

      $this->app->setDBVersion('cocots_moderator', 1);
      $current_version = 1;
    }
    if ($required_version !== $current_version) {
      throw new Exception('Unknow required version for cocots_moderator');
    }
  }

  public function create($email) {
    $columns = array(
      'email',
      'invitation',
    );

    $invitation_key = bin2hex(random_bytes(20));

    $sql = 'INSERT INTO `' . COCOTS_DB_PREFIX . 'moderator` ';
    $sql.= ' ( `' . implode('`, `', $columns) . '` ) ';
    $sql.= ' VALUES ( :'. implode(', :', $columns) . ' ) ';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'email' => $email,
      'invitation' => $invitation_key
    ));
    $id = $this->app->db->lastInsertId();

    $message = '';
    $message.= $this->app->loc->translateSafe('new_moderator_message') . "\n\n";

    $message.= $this->app->loc->translateSafe('moderator_email') . ': ' . $email . "\n\n";

    $message.= $this->app->getBaseUrl() . '/admin' . "\n\n";
    $message.= $this->app->loc->translateSafe('new_moderator_signature') . "\n\n";
    $this->app->notifyAdmins($this->app->loc->translateSafe('new_moderator_subject'), $message);

    // Invitation mail:
    $invit_url = $this->app->getInvitUrl($email, $invitation_key);
    $message = '';
    $message.= $this->app->loc->translateSafe('invit_message') . "\n\n";

    $message.= $this->app->loc->translateSafe('invit_message_url') . ': ' . $invit_url . "\n\n";

    $message.= $this->app->loc->translateSafe('invit_message_signature') . "\n\n";
    $this->app->notify([$email], $this->app->loc->translateSafe('invit_message_subject'), $message);

    return $id;
  }

  public function getActiveModeratorsMails() {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'moderator` WHERE ';
    $sql.= '`status` = \'active\'';
    $sth = $this->app->db->prepare($sql);
    $sth->execute();
    $moderators = $sth->fetchAll(PDO::FETCH_ASSOC);
    $r = array();
    foreach ($moderators as $moderator) {
      array_push($r, $moderator['email']);
    }
    return $r;
  }

  public function getById($id) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'moderator` WHERE ';
    $sql.= '`id` = :id';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':id', $id, PDO::PARAM_STR);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    return $row;
  }

  public function getByEmail($email) {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'moderator` WHERE ';
    $sql.= '`email` = :email';
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(':email', $email, PDO::PARAM_STR);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    return $row;
  }

  public function list() {
    $sql = 'SELECT * FROM `' . COCOTS_DB_PREFIX . 'moderator` ';
    $sql.= ' ORDER BY `id` ASC ';
    $sth = $this->app->db->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
    return $rows;
  }

  public function revoke($id) {
    $sql = 'UPDATE `' . COCOTS_DB_PREFIX . 'moderator` ';
    $sql.= ' SET `status`= \'revoked\' ';
    $sql.= ' , `revocation_date`=NOW() ';
    $sql.= ' , `password`=NULL ';
    $sql.= ' , `invitation`=NULL ';
    $sql.= ' WHERE `id`=:id';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'id' => $id
    ));
  }

  public function activate($id, $password) {
    $sql = 'UPDATE `' . COCOTS_DB_PREFIX . 'moderator` ';
    $sql.= ' SET `status`= \'active\' ';
    $sql.= ' , `activation_date`=NOW() ';
    $sql.= ' , `password`=:password ';
    $sql.= ' , `invitation`=NULL ';
    $sql.= ' WHERE `id`=:id';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'id' => $id,
      'password' => password_hash($password, PASSWORD_DEFAULT)
    ));
  }

  public function delete($id) {
    $sql = 'DELETE FROM `' . COCOTS_DB_PREFIX . 'moderator` ';
    $sql.= ' WHERE `id`=:id';
    $sth = $this->app->db->prepare($sql);
    $sth->execute(array(
      'id' => $id
    ));
  }

  public function check($id) {
    $moderator = $this->getById($id);
    if (!$moderator) {
      return false;
    }
    if ($moderator['status'] !== 'active') {
      return false;
    }
    if (!$moderator['password']) {
      error_log('Seems we have moderators active but with no password... This is not expected.');
      return false;
    }
    return true;
  }

  public function authent($email, $password) {
    $moderator = $this->getByEmail($email);
    if (!$moderator) {
      return false;
    }
    if ($moderator['status'] !== 'active') {
      return false;
    }
    if (!$moderator['password']) {
      error_log('Seems we have moderators active but with no password... This is not expected.');
      return false;
    }
    if (!password_verify($password, $moderator['password'])) {
      return false;
    }
    return $moderator['id'];
  }
}
