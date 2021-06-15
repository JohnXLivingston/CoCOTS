<?php
require('../lib/init.php');
$app = new Application(true);


?><!DOCTYPE html>
<html
  lang="<?php echo $app->loc->currentLang(); ?>"
  dir="<?php echo $app->loc->currentDir(); ?>"
>
  <head>
      <meta charset="UTF-8">
      <title><?php echo htmlspecialchars($app->loc->translate('admin_title')) ?></title>
      <link rel="stylesheet" href="../static/styles.css">
  </head>
  <body>
    TODO.
  </body>
</html>
