<?php
require('./lib/headers.php');
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
      <title><?php echo $app->loc->translate('title') ?></title>
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles.css">
  </head>
  <body>
    <?php if (!$saved) { ?>
      <form method="POST" <?php if ($app->debug_mode) { ?>novalidate<?php } ?>>
        <?php if (defined('COCOTS_HELP_TEXT')) { ?>
          <p>
            <?php echo COCOTS_HELP_TEXT; ?>
          </p>
        <?php } ?>
        <p>
          <?php echo $form->getField('website_name')->getLabelHtml(); ?>
          <div class="surrounded">
            <span>https://</span>
            <?php echo $form->getField('website_name')->html(); ?>
            <span>.<?php echo htmlspecialchars(COCOTS_HOSTING_DOMAIN); ?></span>
          </div>
        </p>
        <?php
          if ($form->getField('website_name')->hasErrorCode('error_website_name_already_exists')) {
            ?><div class="error field-error-annotation">
              <?php echo $app->loc->translate('error_website_name_already_exists'); ?>
            </div><?php
          }
        ?>

        <p>
          <?php echo $form->getField('email')->getLabelHtml(); ?>
          <?php echo $form->getField('email')->html(); ?>
        </p>

        <p>
          <?php echo $form->getField('confirm_email')->getLabelHtml(); ?>
          <?php echo $form->getField('confirm_email')->html(); ?>
        </p>
        <?php
          if ($form->getField('confirm_email')->hasErrorCode('error_confirm_email')) {
            ?><div class="error field-error-annotation">
              <?php echo $app->loc->translate('error_confirm_email'); ?>
            </div><?php
          }
        ?>

        <?php if ($form->hasField('website_type')) { ?>
          <p>
            <?php echo $form->getField('website_type')->getLabelHtml(); ?>
            <?php echo $form->getField('website_type')->html(); ?>
          </p>
        <?php } ?>

        <?php
          $plugins_fields = $form->getPluginsFields();
          if (count($plugins_fields) > 0) {
            ?>
            <fieldset>
              <legend>
                <?php echo $app->loc->translate('plugins_list') ?>
              </legend>
              <ul>
                <?php foreach($plugins_fields as $idx => $plugin_field) { ?>
                  <li>
                    <?php echo $plugin_field->html(); ?>
                    <?php echo $plugin_field->getLabelHtml(); ?>
                  </li>
                <?php } ?>
              </ul>
            </fieldset>
            <?php
          }
        ?>

        <?php if ($form->hasField('security_question')) { ?>
          <p>
            <?php echo $form->getField('security_question')->getLabelHtml(); ?>
            <?php echo $form->getField('security_question')->html(); ?>
          </p>
          <?php
            if ($form->getField('security_question')->hasErrorCode('error_security_question')) {
              ?><div class="error field-error-annotation">
                <?php echo $app->loc->translate('error_security_question'); ?>
              </div><?php
            }
          ?>
        <?php } ?>

        <input name="submit" id="submit" tabindex="5" value="<?php echo $app->loc->translate('validate') ?>" type="submit">
        <?php
          if ($error_on_save) {
            ?><div class="error form-error-annotation">
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
            <div class="error_messages">
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
      <div class="request-transmitted">
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
