<?php
require('../lib/init.php');
try {
  global $app;
  $app = new Application(true);
} catch (CocotsSmartException $e) {
  http_response_code(500);
  echo $e->printErrorPage();
  exit(0);
} catch (Exception $e) {
  http_response_code(500);
  exit(0);
}


?><!DOCTYPE html>
<html
  lang="<?php echo $app->loc->currentLang(); ?>"
  dir="<?php echo $app->loc->currentDir(); ?>"
>
  <head>
      <meta charset="UTF-8">
      <title><?php echo htmlspecialchars($app->loc->translate('admin_title')) ?></title>
      <link rel="stylesheet" href="/static/styles.css">
  </head>
  <body>
    TODO.
  </body>
</html>
