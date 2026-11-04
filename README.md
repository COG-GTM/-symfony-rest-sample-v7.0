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

`composer install` generates a random `APP_SECRET` in the uncommitted
`.env.local` file for local development. In any other environment, define a
unique and private `APP_SECRET` (e.g. `php -r "echo bin2hex(random_bytes(32));"`)
in a real environment variable or in the [secrets vault][5]; never commit it.
The application refuses to boot when `APP_SECRET` is empty or a value that was
published in this repository.

If you have [installed Symfony][4] binary, run this command:

```bash
$ cd my_project/
$ symfony serve
```

Then access the application in your browser at the given URL (<https://localhost:8000> by default).

If you don't have the Symfony binary installed, run `php -S localhost:8000 -t public/`
to use the built-in PHP web server or [configure a web server][3] like Nginx or
Apache to run the application.

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
