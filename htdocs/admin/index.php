<?php
require('../lib/init.php');
try {
  $authenticated = false;
  if (($_SERVER['PHP_AUTH_USER'] ?? '') === COCOTS_ADMIN_USER && ($_SERVER['PHP_AUTH_PW'] ?? '') === COCOTS_ADMIN_PASSWORD) {
    $authenticated = true;
  }
  if (!$authenticated) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    http_response_code(401);
    echo 'Unauthorized';
    exit;
  }

  $app = new Application(true);

  $accounts = $app->accounts->list();

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
      <title><?php echo htmlspecialchars($app->loc->translate('admin_title')) ?></title>
      <link rel="stylesheet" href="/static/styles.css">
      <link rel="stylesheet" href="/static/styles_admin.css">
  </head>
  <body>
    <table>
      <tr>
        <th><?php echo $app->loc->translate('account_id'); ?></th>
        <th><?php echo $app->loc->translate('account_name'); ?></th>
        <th><?php echo $app->loc->translate('account_email'); ?></th>
        <th><?php echo $app->loc->translate('account_type'); ?></th>
        <th><?php echo $app->loc->translate('account_plugins'); ?></th>
        <th><?php echo $app->loc->translate('account_status'); ?></th>
        <th><?php echo $app->loc->translate('account_creation_date'); ?></th>
        <th><?php echo $app->loc->translate('account_activation_date'); ?></th>
        <th><?php echo $app->loc->translate('account_deactivation_date'); ?></th>
        <th><?php echo $app->loc->translate('account_deletion_date'); ?></th>
      </tr>
      <?php foreach ($accounts as $account) { ?>
        <tr>
          <td><?php echo htmlspecialchars($account['id']); ?></td>
          <td><?php echo htmlspecialchars($account['name']); ?></td>
          <td><?php echo htmlspecialchars($account['email']); ?></td>
          <td><?php echo htmlspecialchars($account['type']); ?></td>
          <td><?php
            $plugins = json_decode($account['plugins'] ?? '[]');
            echo implode(', ', $plugins);
          ?></td>
          <td><?php echo htmlspecialchars($account['status']); ?></td>
          <td><?php echo htmlspecialchars($account['creation_date']); ?></td>
          <td><?php echo htmlspecialchars($account['activation_date']); ?></td>
          <td><?php echo htmlspecialchars($account['deactivation_date']); ?></td>
          <td><?php echo htmlspecialchars($account['deletion_date']); ?></td>
        </tr>
      <?php } ?>
    </table>
  </body>
</html>
