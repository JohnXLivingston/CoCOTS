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
      <title><?php echo $app->loc->translate('login_title') ?></title>
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles.css">
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles_admin.css">
  </head>
  <body>
    <form method="POST" class="login-form">
      <fieldset>
        <legend><?php echo $app->loc->translate('login') ?></legend>
        <p>
          <label for="login"><?php echo $app->loc->translate('login_user') ?></label>
          <input type="text" name="login" autofocus id="login" value="<?php echo htmlspecialchars($_POST['login'] ?? '') ?>">
        </p>
        <p>
          <label for="password"><?php echo $app->loc->translate('login_password') ?></label>
          <input type="password" name="password" id="password" value="">
        </p>
        <?php
          if ($loginError) {
            ?>
              <div class="error">
                <?php echo $app->loc->translate('login_error') ?>
              </div>
            <?php
          }
        ?>
        <p>
          <input type="submit" name="submit" value="<?php echo $app->loc->translate('login'); ?>">
        </p>
      </fieldset>
    </form>
  </body>
</html>
<?php

exit;