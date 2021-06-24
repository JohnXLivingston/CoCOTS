# Installation

## First installation

### TODO

TODO: explain how to install from git and configure

### Install Composer

Note: replace `www-data` by the actual web user.

```bash
sudo -u www-data mkdir bin;
cd bin;
sudo -u www-data sh ../build/install-composer.sh;
cd ..;
sudo -u www-data php bin/composer.phar update;
```

### DB installation

Connect as an admin
TODO: be more explicit.

## Updates

TODO: be more explicit

```bash
git pull
sudo -u www-data php bin/composer.phar update;
```

Connect to the admin interface to update DB if necessary.
