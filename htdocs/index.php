<?php
require('./lib/init.php');
try {
  $app = new Application();
  $app->connectToDB(false);

  $saved = false;
  $error_on_save = false;
  
  $form = $app->getForm('creation');
  
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
} catch (CocotsSmartException $e) {
  http_response_code(500);
  echo $e->printErrorPage();
  exit(0);
} catch (Exception $e) {
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
      <title><?php echo $app->loc->translate('title') ?></title>
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles.css">
      <?php
        if(defined('COCOTS_CUSTOM_CSS')) {
          echo '<style>';
          echo COCOTS_CUSTOM_CSS;
          echo '</style>';
        }
      ?>
  </head>
  <body id="register-form">
    <?php if (!$saved) { ?>
      <form method="POST" <?php if ($app->debug_mode) { ?>novalidate<?php } ?>>
        <?php if (defined('COCOTS_HELP_TEXT')) { ?>
          <div class="alert alert-primary">
            <?php echo COCOTS_HELP_TEXT; ?>
        </div>
        <?php } ?>

        <div class="mt-3">
          <?php echo $form->getField('website_title')->getLabelHtml('form-label'); ?>
          <?php echo $form->getField('website_title')->html(); ?>
        </div>

        <div class="mt-3">
          <?php echo $form->getField('website_name')->getLabelHtml('form-label'); ?>
          <span class="input-group">
            <span class="input-group-text">https://</span>
            <?php echo $form->getField('website_name')->html(); ?>
            <span class="input-group-text">.<?php echo htmlspecialchars(COCOTS_HOSTING_DOMAIN); ?></span>
          </span>
        </row>
        <?php
          if ($form->getField('website_name')->hasErrorCode('error_website_name_already_exists')) {
            ?><div class="invalid-feedback d-block">
              <?php echo $app->loc->translate('error_website_name_already_exists'); ?>
            </div><?php
          }
        ?>

        <div class="mt-3">
          <?php echo $form->getField('email')->getLabelHtml('form-label'); ?>
          <?php echo $form->getField('email')->html(); ?>
        </div>

        <div class="mt-3">
          <?php echo $form->getField('confirm_email')->getLabelHtml('form-label'); ?>
          <?php echo $form->getField('confirm_email')->html(); ?>
        </div>
        <?php
          if ($form->getField('confirm_email')->hasErrorCode('error_confirm_email')) {
            ?><div class="invalid-feedback d-block">
              <?php echo $app->loc->translate('error_confirm_email'); ?>
            </div><?php
          }
        ?>

        <?php if ($form->hasField('website_type')) { ?>
          <div class="mt-3">
            <?php echo $form->getField('website_type')->getLabelHtml('form-label'); ?>
            <?php echo $form->getField('website_type')->html(); ?>
          </div>
        <?php } ?>

        <?php
          $plugins_fields = $form->getPluginsFields();
          if (count($plugins_fields) > 0) {
            ?>
            <fieldset>
              <legend>
                <?php echo $app->loc->translate('plugins_list') ?>
              </legend>
              <?php foreach($plugins_fields as $idx => $plugin_field) { ?>
                <div class="form-check">
                  <?php echo $plugin_field->html(); ?>
                  <?php echo $plugin_field->getLabelHtml('form-check-label'); ?>
                </div>
              <?php } ?>
            </fieldset>
            <?php
          }
        ?>

        <?php if ($form->hasField('security_question')) { ?>
          <div class="mt-3">
            <?php echo $form->getField('security_question')->getLabelHtml('form-label'); ?>
            <?php echo $form->getField('security_question')->html(); ?>
          </div>
          <?php
            if ($form->getField('security_question')->hasErrorCode('error_security_question')) {
              ?><div class="invalid-feedback d-block">
                <?php echo $app->loc->translate('error_security_question'); ?>
              </div><?php
            }
          ?>
        <?php } ?>

        <input name="submit" id="submit"
          tabindex="5"
          value="<?php echo $app->loc->translate('validate') ?>"
          type="submit"
          class="btn btn-primary mt-3"
        >
        <?php
          if ($error_on_save) {
            ?><div class="alert alert-danger">
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
    <?php } else { ?>
      <div class="alert alert-success mt-3">
        <h1>
          <?php echo $app->loc->translate('request_transmitted'); ?>
        </h2>
        <p>
          <?php echo $app->loc->translate('will_be_notified_when_approved'); ?>
        </p>
      </div>
    <?php } ?>
  </body>
</html>
