<?php
require('./lib/init.php');

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
      <title><?php echo $app->loc->translate('title') ?></title>
      <link rel="stylesheet" href="static/styles.css">
  </head>
  <body>
    <div class="form">
      <form id="contactform" method="POST">
        <p class="contact">
          <label for="website_name">
            <?php echo $form->getField('website_name')->getLabelHtml(); ?>
          </label>
        </p>
        <?php echo $form->getField('website_name')->html(); ?>

        <p class="contact">
          <label for="email">
            <?php echo $form->getField('email')->getLabelHtml(); ?>
          </label>
        </p>
        <?php echo $form->getField('email')->html(); ?>

        <fieldset>
          <label>
            <?php echo $app->loc->translate('website_type') ?>
          </label>
          <label class="site-type">
            <select class="select-style" name="site_type">
              <option value=""><?php echo $app->loc->translate('website_type') ?></option>
              <option value="01"><?php echo $app->loc->translate('website_type_1') ?></option>
              <option value="02"><?php echo $app->loc->translate('website_type_2') ?></option>
            </select>
          </label>
        </fieldset>

        <div>
            <p class="contact">
              <label>
                <?php echo $app->loc->translate('plugin_list') ?>
              </label>
            </p>
            <div>
                <input type="checkbox" id="plugin_noizetier" name="plugin_noizetier">
                <label for="plugin_noizetier">NoiZetier</label>
            </div>
        </div>
        <input class="button" name="submit" id="submit" tabindex="5" value="<?php echo $app->loc->translate('validate') ?>" type="submit">
      </form>
    </div>
  </body>
</html>
