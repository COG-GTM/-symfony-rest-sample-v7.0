Symfony Demo Application
========================

The "Symfony Demo Application" is a reference application created to show how
to develop applications following the [Symfony Best Practices][1].

Requirements
------------

  * PHP 7.3 or higher;
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements][2].

Installation
------------

[Download Symfony][4] to install the `symfony` binary on your computer and run
this command:

```bash
$ symfony new --demo my_project
```

Alternatively, you can use Composer:

```bash
$ composer create-project symfony/symfony-demo my_project
```

Usage
-----

There's no need to configure anything to run the application. If you have
[installed Symfony][4] binary, run this command:

```bash
$ cd my_project/
$ symfony serve
```

Then access the application in your browser at the given URL (<https://localhost:8000> by default).

If you don't have the Symfony binary installed, run `php -S localhost:8000 -t public/`
to use the built-in PHP web server or [configure a web server][3] like Nginx or
Apache to run the application.

### Application secret

`APP_SECRET` signs URIs, CSRF tokens and remember-me cookies, so it must never
be committed. The value in `.env` is intentionally empty; `.env.dev` and
`.env.test` hold throwaway values that are only loaded in those environments.
In every other environment (e.g. `APP_ENV=prod`) generate a unique value and
provide it as a real environment variable, in `.env.local` or via the
[secrets vault][5]:

```bash
$ php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

The kernel refuses to boot outside `dev`/`test` when `APP_SECRET` is empty or
one of the publicly known values listed in `App\Kernel::PUBLIC_SECRETS`. Any
deployment that ever ran with the previously committed value must rotate it.

Tests
-----

Execute this command to run tests:

```bash
$ cd my_project/
$ ./bin/phpunit
```

[1]: https://symfony.com/doc/current/best_practices.html
[2]: https://symfony.com/doc/current/reference/requirements.html
[3]: https://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html
[4]: https://symfony.com/download
[5]: https://symfony.com/doc/current/configuration/secrets.html
