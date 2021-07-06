<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

abstract class CocotsPresets {
  protected $app;

  public function __construct($app){
    $this->app = $app;
  }

  /**
   * @return configOk boolean
   */
  public function checkConfig() {
    return true;
  }

  /**
   * @return types null || array({value: '...', label: '...'})
   */
  abstract public function websiteTypes();

  /**
   * @return plugins null || array({value: '...', label: '...', default: true|false, disabled: true})
   */
  abstract public function websitePlugins();

  /**
   * @param $account
   * @return success boolean | 'waiting'
   */
  abstract public function activateAccount($account);

  /**
   * @param $account
   * @return success boolean | 'waiting'
   */
  abstract public function disableAccount($account);

  /**
   * @param $account
   * @return success boolean | 'waiting'
   */
  abstract public function deleteAccount($account);

  /**
   * This function check the account state, depending on deployment method.
   * For example, if we have to wait for an async script to complete.
   * @param $account
   * @return success boolean | 'waiting'
   */
  abstract public function checkAccountProcessing($account);

  /**
   * This function reset everything so that the account processing can be done again.
   * @param $account
   * @return success boolean
   */
  abstract public function resetAccountProcessing($account);
}
