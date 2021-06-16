<?php
require('./lib/init.php');
try {
  global $app;
  $app = new Application();
  
  $form = $app->getForm('creation');
  
  if ($_POST['submit']) {
    $form->readPost();

    if ($form->check()) {
      if ($form->save()) {
        require(COCOTS_ROOT_DIR . 'pages/saved.php');
        exit(0);
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
      <title><?php echo htmlspecialchars($app->loc->translate('title')) ?></title>
      <link rel="stylesheet" href="/static/styles.css">
  </head>
  <body>
    <div class="form">
      <form method="POST" <?php if ($app->debug_mode) { ?>novalidate<?php } ?>>
        <p>
          <?php echo $form->getField('website_name')->getLabelHtml(); ?>
          <?php echo $form->getField('website_name')->html(); ?>
        </p>

        <p>
          <?php echo $form->getField('email')->getLabelHtml(); ?>
          <?php echo $form->getField('email')->html(); ?>
        </p>

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
        <input class="button" name="submit" id="submit" tabindex="5" value="<?php echo $app->loc->translate('validate') ?>" type="submit">
      </form>
    </div>
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
  </body>
</html>
