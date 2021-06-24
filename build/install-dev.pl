#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/install-dev.pl
# \brief        Install the application on a dev environment.
# \author       (c)2021		John Livingston		<license@john-livingston.fr>
#----------------------------------------------------------------------------


$| = 1; # autoflush

use strict;
use warnings;

use Cwd;

#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
my $NAME = 'cocots';
my $INSTALL_USER = 'www-data';
my $INSTALL_GROUP = 'www-data';
my $INSTALL_DIR = "/var/www/$NAME/";
my $APACHE_CONFIG_DIR = "/etc/apache2/sites-available/";

# Choose package targets
#-----------------------
for (0..@ARGV-1) {
	if ($ARGV[$_] =~ /^-*install-user=([\w-]+)/i) {
    $INSTALL_USER = $1;
  } elsif ($ARGV[$_] =~ /^-*install-group=([\w-]+)/i) {
    $INSTALL_GROUP = $1;
  } elsif ($ARGV[$_] =~ /^-*install-dir=(.+)(\s|$)/i) {
    $INSTALL_DIR = $1;
  } elsif ($ARGV[$_] =~ /^-*apache-dir=(.+)(\s|$)/i) {
    $APACHE_CONFIG_DIR = $1;
  } else {
    die "There is an unknown parameter: '$ARGV[$_]'.\n"
  }
}
my $APACHE_CONFIG_FILE = "$APACHE_CONFIG_DIR$NAME.conf";

if (!$INSTALL_DIR) {
  die "Invalid install directory: '$INSTALL_DIR'.\n";
}
if ($INSTALL_DIR !~ /^(.*\/)[^\/]+\/$/) {
  die "Invalid install directory '$INSTALL_DIR', cant find the parent dir.\n";
}
my $INSTALL_DIR_PARENT = $1;
if (! -d $INSTALL_DIR_PARENT) {
  die "Install directory parent does not exist: '$INSTALL_DIR_PARENT'.\n";
}
if (!$INSTALL_USER) {
  die "Missing --install-user.\n";
}
if (! -d $APACHE_CONFIG_DIR) {
  die "The apache site-available folder does not exist: '$APACHE_CONFIG_DIR'.\n";
}

my $ret = `sudo true`;
if ($? != 0) { die "Failed to act as root. You must have root rights to install.\n"; }

if ($INSTALL_USER) {
  if (!$INSTALL_GROUP) {
    $INSTALL_GROUP = $INSTALL_USER;
  }
  $ret = `sudo -u $INSTALL_USER true`;
  if ($? != 0) { die "Failed to act as user $INSTALL_USER.\n"; }
}
if ($INSTALL_GROUP) {
  $ret = `sudo -u $INSTALL_USER -g $INSTALL_GROUP true`;
  if ($? != 0) { die "Failed to act as user $INSTALL_USER.\n"; }
}

if ($INSTALL_DIR ne "/var/www/$NAME/") {
  print "Please confirm that you want to install in $INSTALL_DIR/ by typing 'yes'\n";
  my $input = <STDIN>;
  chomp($input);
  if ($input ne 'yes') {
    die "Aborting...\n";
  }
}

my $SOURCE_DIRECTORY = getcwd() . '/';

print "Installing dev environment...\n";
print "Project name: $NAME\n";
print "Current directory (source): $SOURCE_DIRECTORY\n";
print "Target directory: $INSTALL_DIR\n";
print "Apache file: $APACHE_CONFIG_FILE\n";
print "\n";

# Install Apache Configuration
#------------------------------
print "Installation Apache configuration...\n";
$ret = `sudo cp -pr "apache2/cocots-dev.conf" "$APACHE_CONFIG_FILE" && sudo chown root:root "$APACHE_CONFIG_FILE" && sudo chmod 644 "$APACHE_CONFIG_FILE"`;
if ($? != 0) { die "Failed to install $APACHE_CONFIG_FILE.\n"; }

my $INSTALL_DIR_ARMOR = $INSTALL_DIR;
$INSTALL_DIR_ARMOR =~ s/\//\\\//g;
$ret = `sudo sed -i 's/{{COCOTS_INSTALL_DIR}}/$INSTALL_DIR_ARMOR/g' "$APACHE_CONFIG_FILE"`;
$ret = `sudo sed -i 's/{{COCOTS_NAME}}/$NAME/g' "$APACHE_CONFIG_FILE"`;

# Install dirs
#------------------------------
print "Creating dirs...\n";
$ret=`sudo mkdir -p "$INSTALL_DIR" && sudo chown $INSTALL_USER:$INSTALL_GROUP "$INSTALL_DIR" && sudo chmod 755 "$INSTALL_DIR"`;
if ($? != 0) { die "Failed to create dir $INSTALL_DIR.\n"; }

my $INSTALL_CONFIG_DIR = $INSTALL_DIR . 'config/';
$ret=`sudo mkdir -p "$INSTALL_CONFIG_DIR" && sudo chown root:root "$INSTALL_CONFIG_DIR" && sudo chmod 755 "$INSTALL_CONFIG_DIR"`;
if ($? != 0) { die "Failed to create dir $INSTALL_CONFIG_DIR.\n"; }

# Create config file if not here.
#------------------------------
my $INSTALL_CONFIG_FILE = $INSTALL_CONFIG_DIR . 'config.php';
if (! -f $INSTALL_CONFIG_FILE) {
  print "Adding a configuration file...\n";
  $ret=`sudo cp config/config.php.sample "$INSTALL_CONFIG_FILE" && sudo chown root:root "$INSTALL_CONFIG_FILE" && sudo chmod 644 "$INSTALL_CONFIG_FILE"`;
  if ($? != 0) { die "Failed to create config file.\n"; }
} else {
  print "Configuration file already here.\n";
}

# Copy source files
#------------------------------
print "Copying htdocs files...\n";
my $INSTALL_HTDOCS_DIR = $INSTALL_DIR . 'htdocs';
$ret=`sudo rm -rf "$INSTALL_HTDOCS_DIR"`;
if ($? != 0) { die "Failed to delete htdocs.\n"; }

$ret=`sudo cp -pr "htdocs" "$INSTALL_HTDOCS_DIR" && sudo chown -R $INSTALL_USER:$INSTALL_GROUP "$INSTALL_HTDOCS_DIR"`;
if ($? != 0) { die "Failed to copy htdocs.\n"; }
$ret=`sudo chmod -R u+rwX,go+rX,go-w "$INSTALL_HTDOCS_DIR"`;
if ($? != 0) { die "Failed to set htdocs chmod.\n"; }

# Install Composer
#------------------------------
print "Installing composer...\n";
my $INSTALL_BIN_DIR = $INSTALL_DIR . 'bin';
if (! -d $INSTALL_BIN_DIR) {
  $ret=`sudo mkdir -p "$INSTALL_BIN_DIR" && sudo chown -R $INSTALL_USER:$INSTALL_GROUP "$INSTALL_BIN_DIR"`;
  if ($? != 0) { die "Failed to create bin dir.\n"; }
}
if (! -f $INSTALL_BIN_DIR . '/composer.phar') {
  my $INSTALL_COMPOSER_SOURCE = $SOURCE_DIRECTORY . 'build/install-composer.sh';
  $ret=`sudo cp "$INSTALL_COMPOSER_SOURCE" "$INSTALL_BIN_DIR/install-composer.sh" && cd "$INSTALL_BIN_DIR" && sudo -u $INSTALL_USER sh ./install-composer.sh && sudo rm "$INSTALL_BIN_DIR/install-composer.sh" && cd -`;
  if ($? != 0) { die "Failed to install composer: $ret.\n"; }
} else {
  print "Composer is already installed.\n";
}

# Install Composer packages
#------------------------------
print "Installing composer packages...\n";
my $INSTALL_VENDOR_DIR = $INSTALL_DIR . 'vendor';
if (! -d $INSTALL_VENDOR_DIR) {
  $ret=`sudo mkdir -p "$INSTALL_VENDOR_DIR" && sudo chown -R $INSTALL_USER:$INSTALL_GROUP "$INSTALL_VENDOR_DIR"`;
  if ($? != 0) { die "Failed to create vendor dir.\n"; }
}
my $INSTALL_COMPOSERJSON_FILE = $INSTALL_DIR . 'composer.json';
$ret=`sudo cp -p "composer.json" "$INSTALL_COMPOSERJSON_FILE" && sudo chown $INSTALL_USER:$INSTALL_GROUP "$INSTALL_COMPOSERJSON_FILE"`;
if ($? != 0) { die "Failed to copy composer.json.\n"; }
$ret=`sudo -u $INSTALL_USER -- sh -c 'cd "$INSTALL_DIR" && COMPOSER_HOME="${INSTALL_DIR}" php bin/composer.phar update && cd - '`;
if ($? != 0) { die "Failed to update composer packages: $ret.\n"; }

print "Installation complete.\n";

0;
