<?php
require('../lib/init.php');
try {
  $app = new Application();
  $app->connectToDB(true);
  require(COCOTS_ROOT_DIR . 'admin/login.php'); // Ensure the user is logged in.

  if (!$_SESSION['login']) {
    throw new Exception('Should be connected');
  }

  $error_message = false;
  $confirmation_message = false; // TODO: add a confirmation mecanism.

  $action = $_POST['action'] ?? false;
  if ($action === 'set_status') {
    $id = $_POST['id'] ?? false;
    $status = $_POST['status'] ?? false;
    if ($id && $status) {
      if (isset($_POST['confirm']) && $_POST['confirm'] === '1') {
        if ($status === 'active') {
          if (!$app->accounts->activate($id)) {
            $error_message = $app->loc->translate('account_status_failed');
          } else {
            header('Location: ' . $app->getAdminUrl());
            exit;
          }
        } elseif ($status === 'disabled') {
          if (!$app->accounts->disable($id)) {
            $error_message = $app->loc->translate('account_status_failed');
          } else {
            header('Location: ' . $app->getAdminUrl());
            exit;
          }
        } elseif ($status === 'deleted') {
          if (!$app->accounts->delete($id)) {
            $error_message = $app->loc->translate('account_status_failed');
          } else {
            header('Location: ' . $app->getAdminUrl());
            exit;
          }
        } elseif ($status === 'rejected') {
          if (!$app->accounts->reject($id)) {
            $error_message = $app->loc->translate('account_status_failed');
          } else {
            header('Location: ' . $app->getAdminUrl());
            exit;
          }
        }
      } else {
        // We must ask for confirmation
        $account = $app->accounts->getById($id);
        if ($account) {
          $confirmation_message = array(
            'account' => $account,
            'status' => $status,
            'type' => 'set_status'
          );
        }
      }
    }
  } else if ($action === 'send_test_mail' && isset($_SESSION['superadmin']) && $_SESSION['superadmin'] === 1) {
    if (isset($_POST['confirm']) && $_POST['confirm'] === '1') {
      $app->notifyAdmins('Test mail', 'This is a test.');
    } else {
      $confirmation_message = array(
        'type' => 'send_test_mail'
      );
    }
  }

  $sort_info = $app->accounts->readSort(isset($_GET['sort']) ? $_GET['sort'] : null);
  $accounts = $app->accounts->list(isset($_GET['sort']) ? $_GET['sort'] : null);

} catch (CocotsSmartException $e) {
  http_response_code(500);
  echo $e->printErrorPage();
  exit(0);
} catch (Exception | Error $e) {
  http_response_code(500);
  exit(1);
}

function display_status_button($id, $value, $label, $class) {
  global $app;
  ?><form class="mb-3" method="POST" action="<?php echo $app->getAdminUrl(); ?>">
    <input type="hidden" name="action" value="set_status">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($value); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
    <input type="submit"
      class="btn <?php echo htmlspecialchars($class); ?>"
      value="<?php echo $label; ?>"
    >
  </form><?php
}

function display_status_badge($status) {
  global $app;
  $account_status_class = '';
  switch($status) {
    case 'waiting':
    case 'processing':
    case 'processing_disabled':
    case 'processing_deleted':
      $account_status_class = 'bg-warning';
      break;
    case 'active':
      $account_status_class = 'bg-success';
      break;
    case 'disabled':
    case 'rejected':
    case 'deleted':
      $account_status_class = 'bg-secondary';
      break;
    default:
      $account_status_class = 'bg-danger';
  }
  echo '<span class="badge ' . $account_status_class . '">';
  if ($app->loc->hasTranslation('status_label_' . $status)) {
    echo $app->loc->translate('status_label_' . $status);
  } else {
    echo htmlspecialchars($status);
  }
  echo '</span>';
}

function display_sort_title($label, $field, $current_sort_info) {
  global $app;

  $sort_param = $field . '-';
  $sort_symbol = '';
  if ($current_sort_info['field'] === $field) {
    if ($current_sort_info['direction'] === 'ASC') {
      $sort_param.= 'desc';
      $sort_symbol = '&#9661;';
    } else {
      $sort_param.= 'asc';
      $sort_symbol = '&#9651;';
    }
  } else {
    $sort_param.= 'asc';
  }
  $url = $app->getAdminUrl($sort_param);
  echo '<a href="' . htmlspecialchars($url) . '" class="sort">';
  echo $label;
  echo '</a>';
  echo ' ' . $sort_symbol;
}

?><!DOCTYPE html>
<html
  lang="<?php echo $app->loc->currentLang(); ?>"
  dir="<?php echo $app->loc->currentDir(); ?>"
>
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?php echo $app->loc->translate('admin_title') ?></title>
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
  <body class="container">
    <div class="row justify-content-between mt-3">
      <div class="col-10">
        <h1><a href="<?php echo $app->getAdminUrl(); ?>">
          <?php echo $app->loc->translate('admin_title') ?>
        </a></h1>
      </div>
      <div class="col-2">
        <a class="btn btn-primary text-nowrap" href="<?php echo $app->getLogoutUrl(); ?>">
          <?php echo $app->loc->translate('logout'); ?>
        </a>
      </div>
    </div>
    <?php if ($error_message) { ?>
      <div class="alert alert-danger">
        <?php echo $error_message; ?>
      </div>
    <?php } ?>
    <?php if ($confirmation_message && $confirmation_message['type'] === 'set_status') { ?>
      <form class="alert alert-warning" method="POST" action="<?php echo $app->getAdminUrl(); ?>">
        <input type="hidden" name="action" value="set_status">
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($confirmation_message['status']); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($confirmation_message['account']['id']); ?>">
        <p>
          <?php echo $app->loc->translate('confirm_set_status'); ?>
        </p>
        <p>
          <?php echo $app->loc->translate('account_name'); ?>:
          <?php echo htmlspecialchars($confirmation_message['account']['name'])  . '.' . $confirmation_message['account']['domain'] ?>
        </p>
        <p>
          <?php echo $app->loc->translate('account_title'); ?>:
          <?php echo htmlspecialchars($confirmation_message['account']['title']); ?>
        </p>
        <p>
          <?php echo $app->loc->translate('account_status'); ?>:
          <?php
            echo display_status_badge($confirmation_message['account']['status']);
            echo ' => ';
            echo display_status_badge($confirmation_message['status']);
          ?>
        </p>
        <input class="btn btn-primary" type="submit" value="<?php echo $app->loc->translate('validate'); ?>">
        <a class="btn btn-secondary" href="<?php echo htmlspecialchars($app->getAdminUrl()); ?>"><?php echo $app->loc->translate('cancel'); ?></a>
      </form>
    <?php } else if ($confirmation_message && $confirmation_message['type'] === 'send_test_mail') { ?>
      <form class="alert alert-warning" method="POST" action="<?php echo $app->getAdminUrl(); ?>">
        <input type="hidden" name="action" value="send_test_mail">
        <input type="hidden" name="confirm" value="1">
        <p>
          Please confirm: sending a mail to «<?php echo htmlspecialchars(join(', ', $app->getModeratorsMails())); ?>»?
        </p>
        <input class="btn btn-primary" type="submit" value="<?php echo $app->loc->translate('validate'); ?>">
        <a class="btn btn-secondary" href="<?php echo htmlspecialchars($app->getAdminUrl()); ?>"><?php echo $app->loc->translate('cancel'); ?></a>
      </form>
    <?php } ?>
    <div class="table-responsive"><table class="table table-striped table-hover table-bordered">
      <thead>
        <tr class="text-nowrap">
          <th scope="col"><?php echo display_sort_title($app->loc->translate('account_id'), 'id', $sort_info); ?></th>
          <th scope="col"><?php echo display_sort_title($app->loc->translate('account_name'), 'name', $sort_info); ?></th>
          <th scope="col"><?php echo display_sort_title($app->loc->translate('account_email'), 'email', $sort_info); ?></th>
          <th scope="col"><?php echo $app->loc->translate('account_type'); ?></th>
          <th scope="col"><?php echo $app->loc->translate('account_plugins'); ?></th>
          <th scope="col"><?php echo display_sort_title($app->loc->translate('account_status'), 'status', $sort_info); ?></th>
          <th scope="col"><?php echo $app->loc->translate('account_action'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($accounts as $account) { ?>
          <tr>
            <td><?php echo htmlspecialchars($account['id']); ?></td>
            <td>
              <?php
              $account_url_html = htmlspecialchars($account['name'] . '.' . $account['domain']);
              echo '<a href="https://' . $account_url_html . '" target="_blank">' . $account_url_html . '</a>';
              echo '<br>';
              echo htmlspecialchars($account['title']);
              ?>
            </td>
            <td><?php echo htmlspecialchars($account['email']); ?></td>
            <td><?php echo htmlspecialchars($account['type']); ?></td>
            <td><?php
              $plugins = json_decode($account['plugins'] ?? '[]');
              echo htmlspecialchars(implode(', ', $plugins));
            ?></td>
            <td>
              <?php display_status_badge($account['status']) ?>
              <?php
                foreach (array('creation_date', 'activation_date', 'deactivation_date', 'deletion_date', 'rejection_date') as $date_field) {
                  if (!isset($account[$date_field])) { continue; }
                  echo '<div class="text-nowrap">';
                  echo $app->loc->translate('account_' . $date_field);
                  echo ': ';
                  echo htmlspecialchars($account[$date_field]);
                  echo '</div>';
                }

                if ($account['activation_mail_sent']) {
                  echo '<div class="text-nowrap">';
                  echo $app->loc->translate('account_activation_mail_sent');
                  echo '</div>';
                }
              ?>
            </td>
            <td><?php
              if ($account['status'] === 'waiting') {
                display_status_button($account['id'], 'active', $app->loc->translate('account_action_status_active'), 'btn-success');
                display_status_button($account['id'], 'rejected', $app->loc->translate('account_action_status_rejected'), 'btn-danger');
              } elseif ($account['status'] === 'disabled') {
                display_status_button($account['id'], 'active', $app->loc->translate('account_action_status_active'), 'btn-success');
                display_status_button($account['id'], 'deleted', $app->loc->translate('account_action_status_deleted'), 'btn-danger');
              } elseif ($account['status'] === 'active') {
                display_status_button($account['id'], 'disabled', $app->loc->translate('account_action_status_disabled'), 'btn-secondary');
                display_status_button($account['id'], 'active', $app->loc->translate('account_action_reprocess'), 'btn-success');
              } elseif ($account['status'] === 'failed') {
                display_status_button($account['id'], 'active', $app->loc->translate('account_action_reprocess'), 'btn-success');
              } elseif ($account['status'] === 'failed_disabled') {
                display_status_button($account['id'], 'disabled', $app->loc->translate('account_action_reprocess'), 'btn-secondary');
              }
            ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table></div>
    <ul class="row justify-content-between mt-3">
      <div class="col-4">
        <?php echo htmlspecialchars($_SESSION['login']); ?>
      </div>
      <?php if (COCOTS_ENABLE_DEBUG) { ?>
        <?php if ($app->debug_mode) { ?>
          <div class="col-2"><a class="btn btn-secondary" target="_blank" href="<?php echo $app->getBaseUrl() ?>/script/check_processing.php">Check Processing</a></div>
          <?php if (isset($_SESSION['superadmin']) && $_SESSION['superadmin'] === 1) { ?>
            <div class="col-2"><form class="" method="POST" action="<?php echo $app->getAdminUrl(); ?>">
              <input type="hidden" name="action" value="send_test_mail">
              <input type="submit"
                class="btn btn-secondary"
                value="Send test mail"
              >
            </form></div>
          <?php } ?>
        <?php } ?>
        <div class="col-2">
          <a class="btn btn-secondary" href="<?php echo $app->getAdminUrl(null, $app->debug_mode ? false : true); ?>">
            Debug <?php echo $app->debug_mode ? 'OFF' : 'ON'; ?>
          </a>
        </div>
      <?php } ?>
    </ul>
  </body>
</html>
