<?php

if(!_COCOTS_INITIALIZED) {
  return;
}

class CocotsExamplePresets extends CocotsPresets {
  public function websiteTypes() {
    return array(
      array(
        'value' => 'website_type_1',
        'label' => $this->app->loc->translate('website_type_1')
      ),
      array(
        'value' => 'website_type_2',
        'label' => $this->app->loc->translate('website_type_2')
      )
    );
  }
}
