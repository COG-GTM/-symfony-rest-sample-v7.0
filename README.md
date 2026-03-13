Symfony REST Sample v7.0
========================

A sample blog application built with **Symfony 6.0** and **PHP 8.0+**, demonstrating
best practices for building web applications following the
[Symfony Best Practices][1]. The project includes a blog with posts, comments,
tags, user authentication, an admin panel, and an RSS feed.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Static Analysis](#static-analysis)
- [Frontend Assets](#frontend-assets)
- [Contributing](#contributing)
- [License](#license)

## Requirements

- **PHP 8.0.2** or higher
- **PDO-SQLite** PHP extension enabled
- **Composer** 2.x
- **Node.js** and **Yarn** (for building frontend assets)
- The [usual Symfony application requirements][2]

## Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/COG-GTM/-symfony-rest-sample-v7.0.git
   cd -symfony-rest-sample-v7.0
   ```

2. **Install PHP dependencies:**

   ```bash
   composer install
   ```

3. **Install frontend dependencies and build assets:**

   ```bash
   yarn install
   yarn build
   ```

4. **Set up the database:**

   The application uses SQLite by default, so no database server is required.
   Run the migrations to create the schema:

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

5. **(Optional) Load sample data:**

   ```bash
   php bin/console doctrine:fixtures:load
   ```

## Usage

Start the local development server using the [Symfony CLI][4]:

```bash
symfony serve
```

Then open <https://localhost:8000> in your browser.

Alternatively, use the built-in PHP web server:

```bash
php -S localhost:8000 -t public/
```

You can also [configure a web server][3] such as Nginx or Apache for production use.

### Default Users

After loading fixtures, the following accounts are available:

| Username    | Password    | Role         |
|-------------|-------------|--------------|
| `jane_admin`| `kitten`    | `ROLE_ADMIN` |
| `tom_admin` | `kitten`    | `ROLE_ADMIN` |
| `john_user` | `kitten`    | `ROLE_USER`  |

## Project Structure

```
src/
├── Command/            # Console commands
├── Controller/         # Web controllers (Blog, Security, User, Admin)
├── DataFixtures/       # Sample data fixtures
├── Entity/             # Doctrine entities (Post, Comment, Tag, User)
├── Event/              # Custom event classes
├── EventSubscriber/    # Event subscribers
├── Form/               # Form types
├── Pagination/         # Pagination utilities
├── Repository/         # Doctrine repositories
├── Security/           # Security voters and providers
├── Twig/               # Twig extensions
└── Utils/              # Utility classes
```

## Testing

The project uses **PHPUnit 9.5** (via the Symfony PHPUnit Bridge) and
[DAMA DoctrineTestBundle][5] for database transaction isolation.

Run the full test suite:

```bash
./bin/phpunit
```

Tests are located in the `tests/` directory:

```
tests/
├── Command/      # Console command tests
├── Controller/   # Functional controller tests
├── Form/         # Form type tests
└── Utils/        # Utility class tests
```

## Static Analysis

[PHPStan][6] is configured at the maximum level for strict static analysis:

```bash
vendor/bin/phpstan analyse
```

## Frontend Assets

Frontend assets are managed with [Webpack Encore][7]. The project uses
Bootstrap (via Bootswatch), jQuery, Font Awesome, Highlight.js, and
Stimulus.

| Command          | Description                            |
|------------------|----------------------------------------|
| `yarn dev`       | Build assets for development           |
| `yarn watch`     | Watch for changes and rebuild          |
| `yarn build`     | Build optimized assets for production  |
| `yarn dev-server`| Start the Webpack dev server           |

## Contributing

Contributions are welcome. Please see [CONTRIBUTING.md](CONTRIBUTING.md) for
guidelines.

## License

This project is released under the [MIT License](LICENSE).

---

[1]: https://symfony.com/doc/current/best_practices.html
[2]: https://symfony.com/doc/current/reference/requirements.html
[3]: https://symfony.com/doc/current/setup/web_server_configuration.html
[4]: https://symfony.com/download
[5]: https://github.com/dmaicher/doctrine-test-bundle
[6]: https://phpstan.org/
[7]: https://symfony.com/doc/current/frontend.html
