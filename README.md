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

The application uses a local SQLite database (`data/database.sqlite`), which is
not distributed with the code. Create it and, **only in local development
environments**, load the demo fixtures:

```bash
$ cd my_project/
$ php bin/console doctrine:schema:create
$ php bin/console doctrine:fixtures:load
```

> **Warning:** the fixtures create demo accounts with well-known credentials
> (including users with `ROLE_ADMIN`; see `src/DataFixtures/AppFixtures.php`).
> Never load them in a publicly reachable deployment. To create your own users
> run `php bin/console app:add-user`; to remove demo users from an existing
> database run `php bin/console app:delete-user <username>`.

If you have [installed Symfony][4] binary, run this command:

```bash
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
