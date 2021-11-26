# PHP RFC Watch

Watches all votes on RFCs and builds a timeline of who voted +1/-1 when.

See: https://php-rfc-watch.beberlei.de/

## Installation

    git clone git@github.com:beberlei/php-rfc-watch.git
    cd php-rfc-watch
    composer install
    npm install

## Init

To quickly get going you can use sqlite as the database. For this edit the `.env` file and set:

    DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db

To initalize the database schemes use:

    $ php bin/console doctrine:schema:create

## Run

Open two terminals with:

    $ php bin/console server:run
    $ yarn encore dev --watch

Open up `http://localhost:8000`.

Import the current state by calling:

    $ php bin/console php-rfc-watch:synchronize

## Run with Caddy

To run PHP RFC Watch using Caddy HTTP Server see the configuration I use to run it myself:

```
php-rfc-watch.beberlei.de {
    root /var/www/phprfcwatch/web
    log /var/log/caddy/phprfcwatch.log
    errors /var/log/caddy/errors-phprfcwatch.log

    gzip 
    tls off

    git {
        repo github.com/beberlei/php-rfc-watch
        path ../../phprfcwatch
        then composer install
    }

    fastcgi / /var/run/php/php7.0-fpm.sock php {
        index app.php
    }

    rewrite  {
        to {path} {path}/  /app.php
    }
}
```
