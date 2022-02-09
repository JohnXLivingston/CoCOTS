<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

require_once(COCOTS_ROOT_DIR . 'lib/forms/abstract.php');

class InvitForm extends Form {
  protected $moderator_id = false;
  public function setModeratorId($id) {
    $this->moderator_id = $id;
  }

  public function getFormName() {
    return 'invit';
  }

  protected function initFields() {
    $this->fields['password'] = new PasswordField($this, 'password', array(
      'required' => true,
      'autofocus' => true,
      'label' => $this->app->loc->translateSafe('invit_password'),
      'pattern' => '^\S{8,512}$',
      'placeholder' => true,
      'title' => $this->app->loc->translateSafe('invit_password_constraints'),
      'aria-label' => $this->app->loc->translateSafe('invit_password_constraints')
    ));

    $this->fields['confirm_password'] = new PasswordField($this, 'confirm_password', array(
      'required' => true,
      'label' => $this->app->loc->translateSafe('confirm_invit_password'),
      'placeholder' => true
    ));
  }

  public function check() {
    if (!parent::check()) {
      return false;
    }

    if ($this->fields['password']->getValue() !== $this->fields['confirm_password']->getValue()) {
      $this->fields['password']->addErrorCode('error_confirm_invit_password');
      $this->fields['confirm_password']->addErrorCode('error_confirm_invit_password');
      return false;
    }

    return true;
  }

  public function save() {
    $app = $this->app;
    try {
      $password = $this->fields['password']->getValue();

      // The front-end should have checked the moderator state, but just in case here we test again:
      $id = $this->moderator_id;
      if (!$id) {
        error_log("Missing moderator id.\n");
        return false;
      }

      $moderator = $app->moderators->getById($id);
      if (!$moderator) {
        error_log("Error: moderator {$id} not found.\n");
        return false;
      }

      if ($moderator['status'] !== 'waiting') {
        error_log("Error: moderator {$id} is not waiting, but '{$moderator['status']}'.\n");
        return false;
      }

      $app->moderators->activate($id, $password);

      return true;
    } catch (Exception | Error $e) {
      // We don't want to loose the form content. So we are catching exceptions...
      error_log($e);
      return false;
    }
  }
}
