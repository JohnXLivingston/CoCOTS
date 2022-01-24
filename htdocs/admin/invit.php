<?php
require('../lib/init.php');

try {
  $app = new Application();
  $app->connectToDB(true);

  $email = $_GET['email'] ?? false;
  $key = $_GET['key'] ?? false;

  $form = false;
  $saved = false;
  $error_on_save = false;

  $moderator = $email ? $app->moderators->getByEmail($email) : false;
  if ($moderator && $key && $moderator['status'] === 'waiting' && $moderator['invitation'] === $key) {
    // Ok!
    $form = $app->getForm('invit');
    $form->setModeratorId($moderator['id']);

    if ($_POST['submit'] ?? false) {
      $form->readPost();
  
      if ($form->check()) {
        if ($form->save()) {
          $saved = true;
        } else {
          $error_on_save = true;
        }
      }
    }
  }
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
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?php echo $app->loc->translate('title_invit') ?></title>
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles.css">
      <?php
        if(defined('COCOTS_CUSTOM_CSS') || defined('COCOTS_CUSTOM_ADMIN_CSS')) {
          echo '<style>';
          if(defined('COCOTS_CUSTOM_CSS')) {
            echo COCOTS_CUSTOM_CSS;
            echo "\n";
          }
          if(defined('COCOTS_CUSTOM_ADMIN_CSS')) {
            echo COCOTS_CUSTOM_ADMIN_CSS;
          }
          echo '</style>';
        }
      ?>
  </head>
  <body class="container mt-3">

    <?php
    if (!$form) {
      // The invitation link is not valid.
      ?>
      <div class="alert alert-warning">
          <h1>
            <?php echo $app->loc->translate('invalid_invit'); ?>
          </h2>
          <p>
            <?php echo $app->loc->translate('invalid_invit_text'); ?>
          </p>
        </div>
      <?php
    } else if ($saved) {
      // Yeah!
      ?>
        <div class="alert alert-success">
          <h1>
            <?php echo $app->loc->translate('invit_ok'); ?>
          </h2>
          <p>
            <?php echo $app->loc->translate('invit_ok_text'); ?>
          </p>
          <p>
            <a href="<?php echo htmlspecialchars($app->getAdminUrl()); ?>">
            <?php echo htmlspecialchars($app->getAdminUrl()); ?>
            </a>
          </p>
        </div>
      <?php
    } else {
      // Display the form.
      ?>
      <form method="POST" <?php if ($app->debug_mode) { ?>novalidate<?php } ?>>
        <div class="mt-3 alert alert-info">
          <?php echo $app->loc->translate('invit_text'); ?>
        </div>
        <div class="mt-3">
          <?php echo htmlspecialchars($moderator['email']); ?>
        </div>
        <div class="mt-3">
          <?php echo $form->getField('password')->getLabelHtml('form-label'); ?>
          <?php echo $form->getField('password')->html(); ?>
        </div>
        <div class="mt-3">
          <?php echo $form->getField('confirm_password')->getLabelHtml('form-label'); ?>
          <?php echo $form->getField('confirm_password')->html(); ?>
        </div>
        <?php
          if ($form->getField('confirm_password')->hasErrorCode('error_confirm_invit_password')) {
            ?><div class="invalid-feedback d-block">
              <?php echo $app->loc->translate('error_confirm_invit_password'); ?>
            </div><?php
          }
        ?>
        <input name="submit" class="btn btn-primary mt-3" id="submit" tabindex="5" value="<?php echo $app->loc->translate('validate') ?>" type="submit">
          <?php
            if ($error_on_save) {
              ?><div class="alert alert-danger mt-3">
                <?php echo $app->loc->translate('error_on_save'); ?>
              </div><?php
            }
          ?>
      </form>
        <?php
        if ($app->debug_mode) {
          $error_messages_html = $form->getErrorMessagesHtml();
          if (count($error_messages_html) > 0) {
            ?>
              <div class="alert alert-danger mt-3">
                <ul>
                  <?php
                    foreach ($error_messages_html as $error_message_html) {
                      echo '<li>' . $error_message_html . '</li>';
                    }
                  ?>
                </ul>
              </div>
            <?php
          }
          ?>
        <?php } ?>
      <?php
    }
    ?>

  </body>
</html>
