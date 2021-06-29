<?php

/* This script is meant to be called with php-cli OR via web.
*  It will check waiting accounts states.
*/

if (!defined('STDIN')) {
  // This is a web call.
  require(realpath(__DIR__ . '/../lib/headers.php'));
}
require(realpath(__DIR__ . '/../lib/init.php'));
$app = new Application();
$app->connectToDB(false);

if (!$app->presets->checkConfig()) {
  error_log('Presets is not well configured');
  exit(1);
}

$accounts = $app->accounts->getByStatusLike('processing%');

$results = array();
foreach ($accounts as $account) {
  $result = $app->accounts->checkProcessing($account);
  array_push($results, array(
    'id' => $account['id'],
    'status' => $account['status'],
    'result' => $result
  ));
}

echo json_encode($results) . "\n";
