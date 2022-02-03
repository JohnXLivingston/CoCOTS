<?php

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

$loginError = false;

if (!defined('COCOTS_ADMIN_SESSION_NAME')) {
  throw new CocotsSmartException('Sessions are not correctly configured.');
}
session_name(COCOTS_ADMIN_SESSION_NAME);
session_set_cookie_params(0, '/', $app->getDomain(), $app->isHttps(), true);
session_start();

if (isset($_GET['logout'])) {
  $_SESSION = array();
  session_destroy();
  unset($_SESSION);
  header('Clear-Site-Data: "*"');
  header('Location: ' . $app->getAdminUrl());
  exit;
}

if (isset($_SESSION['login'])) {
  if ($_SESSION['login'] === COCOTS_ADMIN_USER && $_SESSION['superadmin'] === 1) {
    return; // everything is fine.
  }
  if (isset($_SESSION['moderator_id'])) {
    if ($app->moderators->check($_SESSION['moderator_id'])) {
      return; // ok!
    }
    // must be a revoked moderator...
    $_SESSION = array();
  }
} 

if (isset($_POST['login']) && isset($_POST['password'])) {
  if ($_POST['login'] === COCOTS_ADMIN_USER && $_POST['password'] === COCOTS_ADMIN_PASSWORD) {
    // Ok!
    $_SESSION['login'] = $_POST['login'];
    $_SESSION['superadmin'] = 1;
    header('Location: ' . $app->getAdminUrl());
    exit;
  }
  $moderator_id = $app->moderators->authent($_POST['login'], $_POST['password']);
  if ($moderator_id) {
    // Ok!
    $_SESSION['login'] = $_POST['login'];
    $_SESSION['moderator_id'] = $moderator_id;
    header('Location: ' . $app->getAdminUrl());
    exit;
  }

  $loginError = true;
  // Writing log so we can set a fail2ban rule.
  $ip = $_SERVER['REMOTE_ADDR'];
  error_log('CoCOTS admin failed login from IP "' . $ip . '", using login "' . $_POST['login'] . '".');
}

?><!DOCTYPE html>
<html
  lang="<?php echo $app->loc->currentLang(); ?>"
  dir="<?php echo $app->loc->currentDir(); ?>"
>
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?php echo $app->loc->translate('login_title') ?></title>
      <link rel="stylesheet" href="<?php echo $app->getCSSUrl(); ?>">
  </head>
  <body>
    <form method="POST" class="container mt-3">
      <h1><?php echo $app->loc->translate('login') ?></h1>
      <div class="mt-3">
        <label for="login" class="form-label"><?php echo $app->loc->translate('login_user') ?></label>
        <input type="text" name="login" class="form-control" autofocus id="login" value="<?php echo htmlspecialchars($_POST['login'] ?? '') ?>">
      </div>
      <div class="mt-3">
        <label for="password" class="form-label"><?php echo $app->loc->translate('login_password') ?></label>
        <input type="password" name="password" class="form-control" id="password" value="">
      </div>

      <?php
        if ($loginError) {
          ?>
            <div class="alert alert-danger mt-3">
              <?php echo $app->loc->translate('login_error') ?>
            </div>
          <?php
        }
      ?>

      <input type="submit" name="submit" class="btn btn-primary mt-3" value="<?php echo $app->loc->translate('login'); ?>">
    </form>
  </body>
</html>
<?php

exit;