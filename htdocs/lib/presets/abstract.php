<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

abstract class CocotsPresets {
  protected $app;

  public function __construct($app){
    $this->app = $app;
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
   * @return success boolean
   */
  abstract public function activateAccount($account);
}
