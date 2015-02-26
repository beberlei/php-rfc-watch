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
