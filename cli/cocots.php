#!/usr/bin/env php
<?php

if (!$argv[0]) {
  echo "This script must be called in CLI mode.\n";
  exit(1);
}

require(realpath(__DIR__ . '/../htdocs/lib/init.php'));
$app = new Application();
$app->connectToDB(true);

if (!$app->presets->checkConfig()) {
  echo("Presets is not well configured\n");
  exit(1);
}

function print_usage() {
  global $argv;
  echo("{$argv[0]} scope [command] [args1] [args2] ... ?\n");
  echo("List of scope/commands:\n");
  echo("  help;                                 print help.\n");
  echo("  moderators list;                      list moderators.\n");
  echo("  moderators create 'mail@example.com'; Creates a moderator, and send invitation mail.\n");
  echo("  moderators revoke 1;                  Revokes a moderator by his id.\n");
  echo("  moderators delete 1;                  Delete a moderator by his id. Please prefer revocation, and use only deletion before re-creating.\n");
  echo("\n");
}

if (count($argv) === 1) {
  print_usage();
  exit(0);
}

$scope = $argv[1];
$command = $argv[2];
switch ($scope) {
  case 'help':
    print_usage();
    break;
  case 'moderators':
    switch ($command) {
      case 'list':
        list_moderators();
        break;
      case 'create':
        if (!$argv[3]) {
          echo "Missing email addresse.\n";
          exit(1);
        }
        $email = $argv[3];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          echo "Invalid email addresse.\n";
          exit(1);
        }
        
        create_moderator($email);
        break;
      case 'revoke':
        if (!$argv[3]) {
          echo "Missing id.\n";
          exit(1);
        }
        $id = $argv[3];
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
          echo "Invalid id.\n";
          exit(1);
        }
        
        revoke_moderator($id);
        break;
        case 'delete':
          if (!$argv[3]) {
            echo "Missing id.\n";
            exit(1);
          }
          $id = $argv[3];
          if (!filter_var($id, FILTER_VALIDATE_INT)) {
            echo "Invalid id.\n";
            exit(1);
          }
          
          delete_moderator($id);
          break;
      default:
        print_usage();
        exit(1);
    }
    break;
  default:
    print_usage();
    exit(1);
}

function confirm($message) {
  echo $message . " [y/N]\n";
  $confirmation = trim(fgets(STDIN));
  return $confirmation === 'y';
}

function list_moderators() {
  global $app;
  $moderators = $app->moderators->list();
  echo "Moderators number: " . count($moderators) . "\n";
  foreach ($moderators as $moderator) {
    echo "{$moderator['id']} {$moderator['email']} {$moderator['status']}\n";
  }
}

function create_moderator($email) {
  global $app;
  
  $already = $app->moderators->getByEmail($email);
  if ($already) {
    echo "Error: there is already a moderator with this email.\n";
    exit(1);
  }
  
  if (!confirm("Create a new moderator with email '{$email}'?")) {
    echo "Aborting...\n";
    exit(1);
  }

  $app->moderators->create($email);
  
  echo "SUCCESS.\n\n";
  list_moderators();
}

function revoke_moderator($id) {
  global $app;

  $moderator = $app->moderators->getById($id);
  if (!$moderator) {
    echo "Error: moderator {$id} not found.\n";
    exit(1);
  }

  if (!confirm("Revoke moderator n°{$moderator['id']} '{$moderator['email']}'?")) {
    echo "Aborting...\n";
    exit(1);
  }

  $app->moderators->revoke($id);
  
  echo "SUCCESS.\n\n";
  list_moderators();
}

function delete_moderator($id) {
  global $app;

  $moderator = $app->moderators->getById($id);
  if (!$moderator) {
    echo "Error: moderator {$id} not found.\n";
    exit(1);
  }

  if (!confirm("Delete moderator n°{$moderator['id']} '{$moderator['email']}'?")) {
    echo "Aborting...\n";
    exit(1);
  }

  $app->moderators->delete($id);
  
  echo "SUCCESS.\n\n";
  list_moderators();
}

echo "\n";
exit(0);
