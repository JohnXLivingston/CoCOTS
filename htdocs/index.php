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

        <div>
            <p>
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
