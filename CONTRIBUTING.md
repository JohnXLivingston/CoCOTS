# CoCOTS - Contributing

## Install dev environment

Pre-requisite: debial-like LAMP. User must be sudoer. User and group www-date have to exists.

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
# TODO
```

Enable the 9876 port on your apache configuratio by adding `Listing 9876` in /etc/apache2/ports.conf.

And enable the apache virtualost:

```bash
sudo a2ensite cocots && sudo systemctl reload apache2
```

The website will be accessible on [http://localhost:9876](http://localhost:9876).
