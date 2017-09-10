# PHP RFC Watch

Watches all votes on RFCs and builds a timeline of who voted +1/-1 when.

Primarily a playground for React.js+Flux, Webpack, Doctrine CouchDB ODM.

## Installation

Installation for Debian/Ubuntu:

    $ sudo apt-get install couchdb nodejs
    $ curl -XPUT http://localhost:5984/rfcwatch
    $ git clone git@github.com:beberlei/php-rfc-watch.git
    $ cd php-rfc-watch
    $ composer install
    $ npm install

## Run

Open two terminals with:

    $ npm run serve-assets
    $ php app/console server:run

Open up `http://localhost:8000`.

Import the current state by calling:

    $ php app/console rfc-watch:synchronize

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
