# Symfony Demo Application

The Symfony Demo Application is a reference implementation created to showcase best practices for building modern web applications with the Symfony framework. This fully-functional blog application demonstrates real-world patterns including authentication, authorization, database management, form handling, internationalization, and frontend asset compilation.

You can also learn about these practices in [the official Symfony Book][5].

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Application Features](#application-features)
- [Console Commands](#console-commands)
- [Testing](#testing)
- [Code Quality Tools](#code-quality-tools)
- [Project Structure](#project-structure)
- [Database](#database)
- [Frontend Assets](#frontend-assets)
- [Internationalization](#internationalization)
- [Contributing](#contributing)
- [License](#license)

## Features

This demo application includes:

- **Blog System**: Create, read, update, and delete blog posts with rich text content
- **Comment System**: Users can comment on blog posts with real-time validation
- **User Authentication**: Secure login/logout with role-based access control
- **Admin Panel**: Administrative interface for managing blog content
- **Search Functionality**: Full-text search across blog posts
- **Tag Management**: Organize posts with tags
- **Internationalization**: Multi-language support (English, Spanish, French, etc.)
- **Responsive Design**: Bootstrap-based UI that works on all devices
- **Clean URL Structure**: SEO-friendly URLs with proper routing
- **Security**: CSRF protection, password hashing, and authorization voters
- **Email Notifications**: Automated emails for new comments

## Requirements

- **PHP**: 8.2.0 or higher
- **PHP Extensions**:
  - PDO-SQLite (for database)
  - intl (for internationalization)
  - mbstring
  - xml
- **Composer**: For PHP dependency management
- **Node.js & npm/yarn**: For frontend asset compilation
- **Symfony CLI** (optional but recommended): For local development server

For a complete list of requirements, see the [Symfony application requirements][2].

## Installation

### Method 1: Using Symfony CLI (Recommended)

1. [Download Symfony CLI][4] and install it on your computer.

2. Create a new project:

```bash
symfony new --demo my_project
cd my_project/
```

### Method 2: Using Composer

```bash
composer create-project symfony/symfony-demo my_project
cd my_project/
```

### Method 3: Clone this Repository

```bash
git clone https://github.com/symfony/symfony-demo.git my_project
cd my_project/
composer install
```

### Method 4: Deploy on Platform.sh

Click the following button to deploy this project on Platform.sh, the official Symfony PaaS, so you can try it without installing anything locally:

<p align="center">
<a href="https://console.platform.sh/projects/create-project?template=https://raw.githubusercontent.com/symfonycorp/platformsh-symfony-template-metadata/main/symfony-demo.template.yaml&utm_content=symfonycorp&utm_source=github&utm_medium=button&utm_campaign=deploy_on_platform"><img src="https://platform.sh/images/deploy/lg-blue.svg" alt="Deploy on Platform.sh" width="180px" /></a>
</p>

### Post-Installation Setup

After installing via any method, complete the setup:

1. **Install PHP dependencies** (if not already done):
```bash
composer install
```

2. **Set up the database**:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

3. **Install frontend dependencies**:
```bash
npm install
# or
yarn install
```

4. **Build frontend assets**:
```bash
npm run build
# or
yarn build
```

## Usage

### Starting the Application

#### Option 1: Using Symfony CLI (Recommended)

```bash
symfony serve
```

Then access the application at `https://localhost:8000`

#### Option 2: Using PHP Built-in Server

```bash
php -S localhost:8000 -t public/
```

Then access the application at `http://localhost:8000`

#### Option 3: Using a Web Server

Configure [Nginx][3] or Apache to serve the application from the `public/` directory.

### Default User Accounts

The application comes with pre-loaded user accounts for testing:

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| `jane_admin` | `kitten` | ROLE_ADMIN | Administrator with full access |
| `tom_admin` | `kitten` | ROLE_ADMIN | Administrator with full access |
| `john_user` | `kitten` | ROLE_USER | Regular user (can comment) |

### Development Mode

For development with automatic asset recompilation:

```bash
# Terminal 1: Start the Symfony server
symfony serve

# Terminal 2: Watch and rebuild assets on changes
npm run watch
# or
yarn watch
```

## Application Features

### Public Features

- **Browse Posts**: View all blog posts on the homepage
- **Read Posts**: Click on any post to read the full content
- **Search**: Use the search bar to find posts by keywords
- **RSS Feed**: Subscribe to blog updates via RSS
- **Comments**: Authenticated users can leave comments on posts

### Admin Features (requires ROLE_ADMIN)

- **Create Posts**: Write new blog posts with title, summary, content, and tags
- **Edit Posts**: Modify existing posts (only your own or all if admin)
- **Delete Posts**: Remove posts from the system
- **Manage Tags**: Add and organize tags for categorization

### User Features

- **Profile Management**: Edit your profile information
- **Change Password**: Update your account password
- **View Your Posts**: See all posts you've authored

## Console Commands

The application includes several custom CLI commands:

### Add a New User

```bash
php bin/console app:add-user
```

Interactive command to create a new user account. You'll be prompted for:
- Username
- Password
- Email
- Full name
- Role (ROLE_USER or ROLE_ADMIN)

### List All Users

```bash
php bin/console app:list-users
```

Display all registered users. Options:
- `--max-results=N`: Limit the number of results
- `--send-to=email@example.com`: Email the list to specified address

### Delete a User

```bash
php bin/console app:delete-user username
```

Remove a user account from the system.

### Other Useful Commands

```bash
# Clear cache
php bin/console cache:clear

# List all routes
php bin/console debug:router

# List all services
php bin/console debug:container

# Run database migrations
php bin/console doctrine:migrations:migrate

# Load sample data
php bin/console doctrine:fixtures:load
```

## Testing

The application includes a comprehensive test suite with unit and functional tests.

### Run All Tests

```bash
./bin/phpunit
```

### Run Specific Test Suites

```bash
# Run only functional tests
./bin/phpunit tests/Controller/

# Run only unit tests
./bin/phpunit tests/Utils/

# Run with coverage report
./bin/phpunit --coverage-html var/coverage
```

### Test Database

Tests use a separate SQLite database that is automatically created and managed. The DAMA Doctrine Test Bundle ensures database transactions are rolled back after each test, keeping tests isolated.

## Code Quality Tools

### PHPStan (Static Analysis)

```bash
vendor/bin/phpstan analyse src tests
```

### PHP CS Fixer (Code Style)

```bash
# Check code style
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style issues
vendor/bin/php-cs-fixer fix
```

### Symfony Security Checker

```bash
symfony security:check
```

## Project Structure

```
├── assets/                 # Frontend source files (JS, SCSS)
│   ├── js/                # JavaScript modules
│   ├── scss/              # Sass stylesheets
│   └── controllers/       # Stimulus controllers
├── bin/
│   └── console           # Symfony console application
├── config/               # Application configuration
│   ├── packages/         # Bundle configurations
│   ├── routes/           # Routing definitions
│   └── services.yaml     # Service container configuration
├── data/                 # SQLite database file
├── migrations/           # Database migrations
├── public/               # Web server document root
│   ├── index.php         # Front controller
│   └── build/            # Compiled assets (generated)
├── src/
│   ├── Command/          # Console commands
│   ├── Controller/       # HTTP controllers
│   │   └── Admin/        # Admin panel controllers
│   ├── DataFixtures/     # Sample data for development
│   ├── Entity/           # Doctrine ORM entities
│   ├── Event/            # Domain events
│   ├── EventSubscriber/  # Event listeners
│   ├── Form/             # Form types
│   ├── Pagination/       # Pagination utilities
│   ├── Repository/       # Data access layer
│   ├── Security/         # Authorization voters
│   ├── Twig/             # Twig extensions
│   ├── Utils/            # Utility classes
│   └── Kernel.php        # Application kernel
├── templates/            # Twig templates
├── tests/                # PHPUnit tests
├── translations/         # Internationalization files
├── var/                  # Cache, logs (generated)
└── vendor/               # Composer dependencies (generated)
```

### Key Components

**Entities**:
- `Post`: Blog post with title, content, author, tags
- `Comment`: User comments on posts
- `User`: User accounts with authentication
- `Tag`: Post categorization tags

**Controllers**:
- `BlogController`: Public blog interface
- `Admin\BlogController`: Administrative post management
- `SecurityController`: Login/logout handling
- `UserController`: User profile management

**Repositories**:
- `PostRepository`: Custom queries for posts (search, latest, etc.)
- `UserRepository`: User lookup and authentication
- `TagRepository`: Tag management

## Database

The application uses SQLite by default for simplicity, but can be configured to use MySQL, PostgreSQL, or other databases supported by Doctrine.

### Database Configuration

Edit `.env` or create `.env.local`:

```env
# SQLite (default)
DATABASE_URL=sqlite:///%kernel.project_dir%/data/database.sqlite

# MySQL
# DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/symfony_demo?serverVersion=8.0"

# PostgreSQL
# DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/symfony_demo?serverVersion=13&charset=utf8"
```

### Migrations

Database schema is managed through migrations:

```bash
# Create a new migration
php bin/console make:migration

# Execute migrations
php bin/console doctrine:migrations:migrate

# Rollback last migration
php bin/console doctrine:migrations:migrate prev
```

### Fixtures

Sample data is provided via fixtures for development:

```bash
# Load fixtures (WARNING: This will purge existing data)
php bin/console doctrine:fixtures:load

# Load fixtures without confirmation prompt
php bin/console doctrine:fixtures:load --no-interaction
```

## Frontend Assets

Frontend assets are managed with Webpack Encore.

### Asset Structure

- **JavaScript**: Modern ES6+ modules with Stimulus framework
- **Stylesheets**: Sass/SCSS with Bootstrap 5
- **Libraries**: jQuery, Typeahead.js, Bootstrap, FontAwesome, Highlight.js

### Build Commands

```bash
# Development build
npm run dev

# Development build with watch mode
npm run watch

# Production build (minified, optimized)
npm run build

# Development server with HMR
npm run dev-server
```

### Adding New Assets

1. Add your JavaScript/CSS files to `assets/`
2. Import them in `assets/app.js` or `assets/styles/app.scss`
3. Run `npm run build` to compile

## Internationalization

The application supports multiple languages:

- English (en)
- Spanish (es)
- French (fr)
- German (de)
- Italian (it)
- Portuguese (pt_BR)
- Russian (ru)
- Ukrainian (uk)
- Chinese (zh_CN)
- And more...

### Translation Files

Translations are stored in `translations/` directory in XLIFF format:
- `messages.en.xlf`
- `messages.es.xlf`
- `messages.fr.xlf`
- etc.

### Switching Languages

Users can switch languages using the language selector in the application header.

### Adding Translations

1. Extract translatable strings:
```bash
php bin/console translation:extract --force --format=xlf en
```

2. Edit the generated XLIFF files in `translations/`

3. Clear the cache:
```bash
php bin/console cache:clear
```

## Contributing

The Symfony Demo Application is an open source project. Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute.

All contributions must follow the [Symfony contribution requirements][7].

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Resources

- [Symfony Documentation][1]
- [Symfony Best Practices][1]
- [Symfony Requirements][2]
- [Web Server Configuration][3]
- [Download Symfony CLI][4]
- [The Symfony Book][5]

[1]: https://symfony.com/doc/current/best_practices.html
[2]: https://symfony.com/doc/current/setup.html#technical-requirements
[3]: https://symfony.com/doc/current/setup/web_server_configuration.html
[4]: https://symfony.com/download
[5]: https://symfony.com/book
[6]: https://getcomposer.org/
[7]: https://symfony.com/doc/current/contributing/index.html
