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
sudo -u www-data COMPOSER_HOME=${PWD} php bin/composer.phar install;
```

### DB installation

Connect as an admin
TODO: be more explicit.

## Updates

TODO: be more explicit

```bash
git pull
sudo -u www-data php bin/composer.phar install;
```

Connect to the admin interface to update DB if necessary.

## Develop

TODO: be more explicit

Install composer in dev env:

```bash
mkdir bin && cd bin && sh ../build/install-composer.sh && cd ..
```

To try to compile SCSS, just do a compiler update:

```bash
php bin/composer.phar install
```
