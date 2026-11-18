Symfony Demo Application
========================

The "Symfony Demo Application" is a reference application created to show how
to develop applications following the [Symfony Best Practices][1].

Requirements
------------

  * PHP 8.0.2 or higher (see `composer.json`);
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements][2];
  * Composer 2.1 (for example `composer self-update 2.1.14`): the locked
    `symfony/flex` 1.x crashes on Composer 2.2+, and `composer.json` has no
    `allow-plugins` list;
  * Node.js 14 and Yarn 1 to build the JavaScript assets (the `node-sass` 4
    dependency targets old Node versions).

Installation
------------

Clone this repository and install the PHP and JavaScript dependencies:

```bash
$ git clone https://github.com/COG-GTM/-symfony-rest-sample-v7.0.git
$ cd ./-symfony-rest-sample-v7.0/
$ composer install
$ yarn install
$ yarn build
```

`yarn.lock` is older than `package.json`, so `yarn install` updates it; don't
use `--frozen-lockfile`.

The compiled JavaScript is not committed (`public/build/` only holds the CSS,
fonts and `entrypoints.json`), so run `yarn build` (or `yarn dev`/`yarn watch`
while developing) before opening the site. The SQLite databases with sample
data are already committed in `data/`.

Usage
-----

There's no need to configure anything else to run the application. If you have
[installed the Symfony binary][4], run:

```bash
$ symfony serve
```

Then access the application in your browser at the given URL (<https://localhost:8000> by default).

If you don't have the Symfony binary installed, run `php -S localhost:8000 -t public/`
to use the built-in PHP web server or [configure a web server][3] like Nginx or
Apache to run the application.

Sign in with the demo users `john_user` (regular user) or `jane_admin`
(administrator); both use the password `kitten`.

Architecture
------------

[docs/architecture.md](docs/architecture.md) describes how requests, security,
data and assets flow through the application, with a
[diagram](docs/diagrams/architecture.png) whose editable source is
[docs/diagrams/architecture.dot](docs/diagrams/architecture.dot).

Tests
-----

Execute this command to run tests:

```bash
$ ./bin/phpunit
```

[1]: https://symfony.com/doc/current/best_practices.html
[2]: https://symfony.com/doc/current/reference/requirements.html
[3]: https://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html
[4]: https://symfony.com/download
