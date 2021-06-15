<?php
require('./lib/init.php');
$app = new Application();


$form = $app->getForm('creation');

if ($_POST['submit']) {
  $form->readPost();
}

?><!DOCTYPE html>
<html
  lang="<?php echo $app->loc->currentLang(); ?>"
  dir="<?php echo $app->loc->currentDir(); ?>"
>
  <head>
      <meta charset="UTF-8">
      <title><?php echo htmlspecialchars($app->loc->translate('title')) ?></title>
      <link rel="stylesheet" href="static/styles.css">
  </head>
  <body>
    <div class="form">
      <form method="POST">
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
              <label>
                <?php echo $app->loc->translate('plugins_list') ?>
              </label>
              <ul>
                <?php foreach($plugins_fields as $idx => $plugin_field) { ?>
                  <li>
                    <?php echo $plugin_field->html(); ?>
                    <?php echo $plugin_field->getLabelHtml(); ?>
                  </li>
                <?php } ?>
              </ul>
            <?php
          }
        ?>
        <input class="button" name="submit" id="submit" tabindex="5" value="<?php echo $app->loc->translate('validate') ?>" type="submit">
      </form>
    </div>
  </body>
</html>
