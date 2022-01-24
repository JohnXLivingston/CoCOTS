<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

class CocotsSmartException extends Exception {
  public function printErrorPage() {
    ?><!DOCTYPE html>
<html>
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Error</title>
  </head>
  <body>
    <h1>Error</h1>
    <p>
      <?php
        echo htmlspecialchars($this->getMessage());
      ?>
    </p>
  </body>
<?php
  }
}
