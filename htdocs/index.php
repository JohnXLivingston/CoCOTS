<?php
require('./lib/init.php');
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
          <label for="name">
            <?php echo $app->loc->translate('site_name') ?>
          </label>
        </p>
        <input id="name" name="name" placeholder="<?php echo $app->loc->translate('site_name') ?>" required="" tabindex="1" type="text">

        <p class="contact">
          <label for="email">
            <?php echo $app->loc->translate('mail') ?>
          </label>
        </p>
        <input id="email" name="email" placeholder="<?php echo $app->loc->translate('mail_example') ?>" required="" type="email">

        <fieldset>
          <label>
            <?php echo $app->loc->translate('site_type') ?>
          </label>
          <label class="site-type">
            <select class="select-style" name="site_type">
              <option value=""><?php echo $app->loc->translate('site_type') ?></option>
              <option value="01"><?php echo $app->loc->translate('site_type_1') ?></option>
              <option value="02"><?php echo $app->loc->translate('site_type_2') ?></option>
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
