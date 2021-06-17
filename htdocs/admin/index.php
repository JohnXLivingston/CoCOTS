<?php
require('../lib/init.php');
try {
  $authenticated = false;
  if (($_SERVER['PHP_AUTH_USER'] ?? '') === COCOTS_ADMIN_USER && ($_SERVER['PHP_AUTH_PW'] ?? '') === COCOTS_ADMIN_PASSWORD) {
    $authenticated = true;
  }
  if (!$authenticated) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    http_response_code(401);
    echo 'Unauthorized';
    exit;
  }

  $app = new Application(true);
} catch (CocotsSmartException $e) {
  http_response_code(500);
  echo $e->printErrorPage();
  exit(0);
} catch (Exception | Error $e) {
  http_response_code(500);
  exit(1);
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
