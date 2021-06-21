# CoCOTS - Contributing

## Install dev environment

Pre-requisite: debian-like LAMP. User must be sudoer. User and group www-date have to exists.

To install:

```bash
perl build/install-dev.pl
```

This will:

* install application files in /var/www/cocots/ with www-data rights
* initialize the config file
* install the apache config file /etc/apache2/sites-available/cocots.conf

Then, you need to create the database:

```bash
sudo mysql
```

```mysql
CREATE DATABASE cocots_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'cocots_dev'@'localhost' IDENTIFIED BY 'cocots';
GRANT ALL PRIVILEGES ON cocots_dev.* TO 'cocots_dev'@'localhost';
FLUSH PRIVILEGES;
```

Enable the 9876 port on your apache configuratio by adding `Listing 9876` in /etc/apache2/ports.conf.

And enable the apache virtualost:

```bash
sudo a2ensite cocots && sudo systemctl reload apache2
```

The website will be accessible on [http://localhost:9876](http://localhost:9876).

To initialize the Database for the first time, go to the admin page: [http://localhost:9876/admin/](http://localhost:9876/admin/).
As username use `admin`, and see in the config file for the password.

## Debug mode

You can enable the debug_mode by adding `?debug=1` to the url.
In debug_mode:

* Front-end field validation will be disabled
* Form error messages will be printed at the end of the page

You can enable/disable the debug_mode by defining the constant COCOTS_ENABLE_DEBUG in your config.php file.
