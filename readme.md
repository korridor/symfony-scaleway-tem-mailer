# Symfony Scaleway TEM mailer

## Installation

You can install the package via composer with following command:

```bash
composer require korridor/symfony-scaleway-tem-mailer
```

## Usage examples

### Laravel

TODO

## Contributing

I am open for suggestions and contributions. Just create an issue or a pull request.

### Local docker environment

The `docker` folder contains a local docker environment for development.
The docker workspace has composer and xdebug installed.

```bash
docker-compose run workspace bash
```

### Testing

The `composer test` command runs all tests with [phpunit](https://phpunit.de/).
The `composer test-coverage` command runs all tests with phpunit and creates a coverage report into the `coverage` folder.

### Codeformatting/Linting

The `composer fix` command formats the code with [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).
The `composer lint` command checks the code with [phpcs](https://github.com/squizlabs/PHP_CodeSniffer).

## License

This package is licensed under the MIT License (MIT). Please see [license file](license.md) for more information.
