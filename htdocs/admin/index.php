<?php
require('../lib/headers.php');
require('../lib/init.php');
try {
  $app = new Application();
  require(COCOTS_ROOT_DIR . 'admin/login.php'); // Ensure the user is logged in.

  if (!$_SESSION['login']) {
    throw new Exception('Should be connected');
  }
  $app->connectToDB(true);

  $error_message = false;
  $confirmation_message = false; // TODO: add a confirmation mecanism.

  $action = $_POST['action'] ?? false;
  if ($action === 'set_status') {
    $id = $_POST['id'] ?? false;
    $status = $_POST['status'] ?? false;
    if ($id && $status) {
      // TODO: add a confirmation step.
      if ($status === 'active') {
        if (!$app->accounts->activate($id)) {
          $error_message = $app->loc->translate('account_status_failed');
        } else {
          header('Location: ' . $app->getBaseUrl() . '/admin');
          exit;
        }
      } elseif ($status === 'disabled') {
        if (!$app->accounts->disable($id)) {
          $error_message = $app->loc->translate('account_status_failed');
        } else {
          header('Location: ' . $app->getBaseUrl() . '/admin');
          exit;
        }
      } elseif ($status === 'rejected') {
        if (!$app->accounts->reject($id)) {
          $error_message = $app->loc->translate('account_status_failed');
        } else {
          header('Location: ' . $app->getBaseUrl() . '/admin');
          exit;
        }
      }
    }
  }

  $accounts = $app->accounts->list();

} catch (CocotsSmartException $e) {
  http_response_code(500);
  echo $e->printErrorPage();
  exit(0);
} catch (Exception | Error $e) {
  http_response_code(500);
  exit(1);
}

function display_status_button($id, $value, $label) {
  global $app;
  ?><form method="POST">
    <input type="hidden" name="action" value="set_status">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($value); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
    <input type="submit" value="<?php echo $label; ?>">
  </form><?php
}

?><!DOCTYPE html>
<html
  lang="<?php echo $app->loc->currentLang(); ?>"
  dir="<?php echo $app->loc->currentDir(); ?>"
>
  <head>
      <meta charset="UTF-8">
      <title><?php echo $app->loc->translate('admin_title') ?></title>
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles.css">
      <link rel="stylesheet" href="<?php echo $app->getBaseUrl(); ?>/static/styles_admin.css">
  </head>
  <body>
    <ul class="top-menu">
      <li><h1><?php echo $app->loc->translate('admin_title') ?></h1></li>
      <li><a class="logout" href="<?php echo $app->getLogoutUrl(); ?>">
        <?php echo $app->loc->translate('logout'); ?>
      </a></li>
    </ul>
    <?php if ($error_message) { ?>
      <div class="error_messages">
        <?php echo $error_message; ?>
      </div>
    <?php } ?>
    <table>
      <tr>
        <th><?php echo $app->loc->translate('account_id'); ?></th>
        <th><?php echo $app->loc->translate('account_name'); ?></th>
        <th><?php echo $app->loc->translate('account_email'); ?></th>
        <th><?php echo $app->loc->translate('account_type'); ?></th>
        <th><?php echo $app->loc->translate('account_plugins'); ?></th>
        <th><?php echo $app->loc->translate('account_status'); ?></th>
        <th><?php echo $app->loc->translate('account_action'); ?></th>
      </tr>
      <?php foreach ($accounts as $account) { ?>
        <tr>
          <td><?php echo htmlspecialchars($account['id']); ?></td>
          <td><?php echo htmlspecialchars($account['name'] . '.' . $account['domain']); ?></td>
          <td><?php echo htmlspecialchars($account['email']); ?></td>
          <td><?php echo htmlspecialchars($account['type']); ?></td>
          <td><?php
            $plugins = json_decode($account['plugins'] ?? '[]');
            echo htmlspecialchars(implode(', ', $plugins));
          ?></td>
          <td>
            <?php echo htmlspecialchars($account['status']); ?>
            <?php
              foreach (array('creation_date', 'activation_date', 'deactivation_date', 'deletion_date', 'rejection_date') as $date_field) {
                if (!isset($account[$date_field])) { continue; }
                echo '<div class="status-date">';
                echo $app->loc->translate('account_' . $date_field);
                echo ': ';
                echo htmlspecialchars($account[$date_field]);
                echo '</div>';
              }

              if ($account['activation_mail_sent']) {
                echo '<div class="activation-mail-sent">';
                echo $app->loc->translate('account_activation_mail_sent');
                echo '</div>';
              }
            ?>
          </td>
          <td><?php
            if ($account['status'] === 'waiting') {
              display_status_button($account['id'], 'active', $app->loc->translate('account_action_status_active'));
              display_status_button($account['id'], 'rejected', $app->loc->translate('account_action_status_rejected'));
            } elseif ($account['status'] === 'disabled') {
              display_status_button($account['id'], 'active', $app->loc->translate('account_action_status_active'));
            } elseif ($account['status'] === 'active') {
              display_status_button($account['id'], 'disabled', $app->loc->translate('account_action_status_disabled'));
              display_status_button($account['id'], 'active', $app->loc->translate('account_action_reprocess'));
            } elseif ($account['status'] === 'failed') {
              display_status_button($account['id'], 'active', $app->loc->translate('account_action_reprocess'));
            } elseif ($account['status'] === 'failed_disabled') {
              display_status_button($account['id'], 'disabled', $app->loc->translate('account_action_reprocess'));
            }
          ?></td>
        </tr>
      <?php } ?>
    </table>
  </body>
</html>
